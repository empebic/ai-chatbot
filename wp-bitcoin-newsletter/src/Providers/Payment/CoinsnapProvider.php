<?php

namespace WpBitcoinNewsletter\Providers\Payment;

use WpBitcoinNewsletter\Admin\Settings;
use WpBitcoinNewsletter\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CoinSnap payment provider implementation.
 */
class CoinsnapProvider implements PaymentProviderInterface {
    /** @inheritDoc */
    public function create_invoice( int $formId, int $amount, string $currency, array $subscriberData ): array {
        $settings = Settings::getSettings();
        $apiKey   = $settings['coinsnap_api_key'];
        $storeId  = $settings['coinsnap_store_id'];
        $apiBase  = rtrim( $settings['coinsnap_api_base'] ?: Constants::COINSNAP_DEFAULT_API_BASE, '/' );
        if ( ! $apiKey || ! $storeId ) {
            return [];
        }
        $endpoints = [
            $apiBase . sprintf( Constants::COINSNAP_INVOICES_ENDPOINT_V1, rawurlencode( $storeId ) ),
            $apiBase . sprintf( Constants::COINSNAP_INVOICES_ENDPOINT_ALT, rawurlencode( $storeId ) ),
        ];
        $payload   = [
            'amount'    => (string) $amount,
            'currency'  => $currency,
            'buyerEmail'=> isset( $subscriberData['email'] ) ? (string) $subscriberData['email'] : '',
            'metadata'  => [
                'form_id' => $formId,
                'email'   => (string) ( $subscriberData['email'] ?? '' ),
            ],
            'checkout'  => [
                'defaultPaymentMethod' => 'LightningNetwork',
            ],
        ];
        $payload = apply_filters( 'wpbn_coinsnap_invoice_payload', $payload, $formId, $subscriberData );
        $args    = [
            'method'  => 'POST',
            'headers' => [
                // New API header per latest docs; keep Authorization for backward compatibility.
                'X-Api-Key'    => $apiKey,
                'Authorization'=> 'token ' . $apiKey,
                'Content-Type' => 'application/json',
                'accept'       => 'application/json',
            ],
            'timeout' => 20,
            'body'    => wp_json_encode( $payload ),
        ];
        $args = apply_filters( 'wpbn_coinsnap_request_args', $args, $formId );
        foreach ( $endpoints as $url ) {
            $res = wp_remote_request( $url, $args );
            /** Action: on Coinsnap response (raw) */
            do_action( 'wpbn_coinsnap_response', $res, $formId );
            if ( is_wp_error( $res ) ) {
                continue;
            }
            $code = wp_remote_retrieve_response_code( $res );
            $body = json_decode( wp_remote_retrieve_body( $res ), true );
            if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
                $invoiceId  = isset( $body['id'] ) ? (string) $body['id'] : '';
                $paymentUrl = isset( $body['checkoutLink'] ) ? (string) $body['checkoutLink'] : '';
                if ( $invoiceId && $paymentUrl ) {
                    return [
                        'invoice_id'  => $invoiceId,
                        'payment_url' => $paymentUrl,
                    ];
                }
            }
        }
        return [];
    }

    /** @inheritDoc */
    public function handle_webhook( array $request ): array {
        do_action( 'wpbn_coinsnap_webhook_received', $request );
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
     * Verify webhook HMAC signature.
     *
     * @return bool True if signature valid.
     */
    public static function verify_signature(): bool {
        $settings = Settings::getSettings();
        $secret   = (string) $settings['coinsnap_webhook_secret'];
        if ( ! $secret ) {
            return false;
        }
        $headers = [
            isset( $_SERVER['HTTP_BTCPAY_SIGNATURE'] ) ? (string) $_SERVER['HTTP_BTCPAY_SIGNATURE'] : '',
            isset( $_SERVER['HTTP_X_COINSNAP_SIGNATURE'] ) ? (string) $_SERVER['HTTP_X_COINSNAP_SIGNATURE'] : '',
            isset( $_SERVER['HTTP_X_SIGNATURE'] ) ? (string) $_SERVER['HTTP_X_SIGNATURE'] : '',
        ];
        $payload = file_get_contents( 'php://input' ) ?: '';
        if ( ! $payload ) {
            return false;
        }
        $raw        = hash_hmac( 'sha256', $payload, $secret );
        $withPrefix = 'sha256=' . $raw;
        foreach ( $headers as $sig ) {
            if ( ! $sig ) {
                continue;
            }
            if ( hash_equals( $raw, $sig ) || hash_equals( $withPrefix, $sig ) ) {
                return true;
            }
        }
        return false;
    }
}

