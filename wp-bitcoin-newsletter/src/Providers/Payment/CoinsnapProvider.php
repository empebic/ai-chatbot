<?php

namespace WpBitcoinNewsletter\Providers\Payment;

use WpBitcoinNewsletter\Admin\Settings;

class CoinsnapProvider implements PaymentProviderInterface
{
    public function createInvoice(int $formId, int $amount, string $currency, array $subscriberData): array
    {
        $settings = Settings::getSettings();
        $apiKey = $settings['coinsnap_api_key'];
        $storeId = $settings['coinsnap_store_id'];
        $apiBase = rtrim($settings['coinsnap_api_base'], '/');
        if (!$apiKey || !$storeId) {
            return [];
        }
        // Coinsnap API path based on Paywall plugin style (assumed)
        $url = $apiBase . '/api/stores/' . rawurlencode($storeId) . '/invoices';
        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => [
                'form_id' => $formId,
                'email' => (string)$subscriberData['email'],
            ],
        ];
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'token ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 20,
            'body' => wp_json_encode($payload),
        ];
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) return [];
        $code = wp_remote_retrieve_response_code($res);
        $body = json_decode(wp_remote_retrieve_body($res), true);
        if ($code >= 200 && $code < 300 && is_array($body)) {
            $invoiceId = isset($body['id']) ? (string)$body['id'] : '';
            $paymentUrl = isset($body['checkoutLink']) ? (string)$body['checkoutLink'] : '';
            return $invoiceId && $paymentUrl ? [
                'invoice_id' => $invoiceId,
                'payment_url' => $paymentUrl,
            ] : [];
        }
        return [];
    }

    public function handleWebhook(array $request): array
    {
        $invoiceId = isset($request['invoiceId']) ? (string)$request['invoiceId'] : '';
        $type = isset($request['type']) ? (string)$request['type'] : '';
        $paid = in_array($type, ['InvoiceSettled', 'PaymentReceived', 'InvoicePaid'], true);
        return [
            'invoice_id' => $invoiceId,
            'paid' => $paid,
            'metadata' => $request,
        ];
    }

    public static function verifySignature(): bool
    {
        $settings = Settings::getSettings();
        $secret = (string)$settings['coinsnap_webhook_secret'];
        if (!$secret) return false;
        $sig = isset($_SERVER['HTTP_X_SIGNATURE']) ? (string)$_SERVER['HTTP_X_SIGNATURE'] : '';
        $payload = file_get_contents('php://input') ?: '';
        if (!$sig || !$payload) return false;
        $calc = hash_hmac('sha256', $payload, $secret);
        return hash_equals($calc, $sig);
    }
}

