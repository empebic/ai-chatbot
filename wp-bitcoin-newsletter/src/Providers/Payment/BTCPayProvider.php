<?php
/**
 * BTCPay provider.
 *
 * @package wp-bitcoin-newsletter
 */
declare(strict_types=1);

namespace WpBitcoinNewsletter\Providers\Payment;

use WpBitcoinNewsletter\Admin\Settings;
use WpBitcoinNewsletter\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BTCPay Server payment provider implementation.
 */
class BTCPayProvider implements PaymentProviderInterface {
    /** @inheritDoc */
    public function create_invoice( int $formId, int $amount, string $currency, array $subscriberData ): array {
        $s      = Settings::getSettings();
        $host   = rtrim( (string) $s['btcpay_host'], '/' );
        $apiKey = (string) $s['btcpay_api_key'];
        $store  = (string) $s['btcpay_store_id'];
        if ( ! $host || ! $apiKey || ! $store ) {
            return [];
        }
        $url     = $host . sprintf( Constants::BTCPAY_INVOICES_ENDPOINT, rawurlencode( $store ) );
        $payload = [
            'amount'   => (string) $amount,
            'currency' => $currency,
            'metadata' => [
                'form_id' => $formId,
                'email'   => (string) $subscriberData['email'],
            ],
        ];
        $args    = [
            'method'  => 'POST',
            'headers' => [
                'Authorization' => 'token ' . $apiKey,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 20,
            'body'    => wp_json_encode( $payload ),
        ];
        $args = apply_filters( 'wpbn_btcpay_request_args', $args, $formId );
        $res  = wp_remote_request( $url, $args );
        do_action( 'wpbn_btcpay_response', $res, $formId );
        if ( is_wp_error( $res ) ) {
            return [];
        }
        $code = wp_remote_retrieve_response_code( $res );
        $body = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
            $invoiceId  = isset( $body['id'] ) ? (string) $body['id'] : '';
            $paymentUrl = isset( $body['checkoutLink'] ) ? (string) $body['checkoutLink'] : '';
            return $invoiceId && $paymentUrl ? [
                'invoice_id'  => $invoiceId,
                'payment_url' => $paymentUrl,
            ] : [];
        }
        return [];
    }

    /** @inheritDoc */
    public function handle_webhook( array $request ): array {
        do_action( 'wpbn_btcpay_webhook_received', $request );
        $invoiceId = isset( $request['invoiceId'] ) ? (string) $request['invoiceId'] : '';
        $type      = isset( $request['type'] ) ? (string) $request['type'] : '';
        $paid      = in_array( $type, [ 'InvoiceSettled', 'PaymentReceived', 'InvoicePaid' ], true );
        return [
            'invoice_id' => $invoiceId,
            'paid'       => $paid,
            'metadata'   => $request,
        ];
    }

    /**
     * Verify BTCPay webhook signature.
     *
     * @return bool True if valid.
     */
    public static function verify_signature(): bool {
        $s       = Settings::getSettings();
        $secret  = (string) $s['btcpay_webhook_secret'];
        if ( ! $secret ) {
            return false;
        }
        $sig     = isset( $_SERVER['HTTP_BTCPAY_SIGNATURE'] ) ? (string) $_SERVER['HTTP_BTCPAY_SIGNATURE'] : '';
        $payload = file_get_contents( 'php://input' ) ?: '';
        if ( ! $sig || ! $payload ) {
            return false;
        }
        $calc = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
        return hash_equals( $calc, $sig );
    }
}

