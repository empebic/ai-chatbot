<?php

namespace WpBitcoinNewsletter\Providers\Payment;

use WpBitcoinNewsletter\Admin\Settings;

class CoinsnapProvider implements PaymentProviderInterface
{
    public function createInvoice(int $formId, int $amount, string $currency, array $subscriberData): array
    {
        $settings = Settings::getSettings();
        $apiKey = $settings['coinsnap_api_key'];
        // TODO: Implement Coinsnap API call. For now, stub
        $invoiceId = 'coinsnap_' . time();
        $paymentUrl = home_url('/?wpbn_invoice=' . rawurlencode($invoiceId));
        return [
            'invoice_id' => $invoiceId,
            'payment_url' => $paymentUrl,
        ];
    }

    public function handleWebhook(array $request): array
    {
        // TODO: Verify Coinsnap signature and parse
        return [
            'invoice_id' => isset($request['invoice_id']) ? (string)$request['invoice_id'] : '',
            'paid' => !empty($request['paid']),
            'metadata' => $request,
        ];
    }
}

