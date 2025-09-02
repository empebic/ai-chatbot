<?php

namespace WpBitcoinNewsletter\Rest;

use WpBitcoinNewsletter\Services\SyncService;
use WpBitcoinNewsletter\Providers\Payment\CoinsnapProvider;
use WpBitcoinNewsletter\Providers\Payment\BTCPayProvider;
use WpBitcoinNewsletter\Database\Installer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Routes {
    public static function register(): void {
        register_rest_route(
            'wpbn/v1',
            '/payment/coinsnap',
            [
                'methods'             => 'POST',
                'permission_callback' => '__return_true',
                'callback'            => [ __CLASS__, 'coinsnap_webhook' ],
            ]
        );

        register_rest_route(
            'wpbn/v1',
            '/payment/btcpay',
            [
                'methods'             => 'POST',
                'permission_callback' => '__return_true',
                'callback'            => [ __CLASS__, 'btcpay_webhook' ],
            ]
        );

        register_rest_route(
            'wpbn/v1',
            '/status/(?P<invoice>[^/]+)',
            [
                'methods'             => 'GET',
                'permission_callback' => '__return_true',
                'callback'            => [ __CLASS__, 'status' ],
            ]
        );

        register_rest_route(
            'wpbn/v1',
            '/subscribers/(?P<id>\\d+)/resync',
            [
                'methods'             => 'POST',
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
                'callback'            => [ __CLASS__, 'resync' ],
            ]
        );

        register_rest_route(
            'wpbn/v1',
            '/subscribers/bulk-resync',
            [
                'methods'             => 'POST',
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
                'callback'            => [ __CLASS__, 'bulk_resync' ],
            ]
        );
    }

    public static function coinsnap_webhook( $request ) {
        if ( ! CoinsnapProvider::verify_signature() ) {
            return new \WP_Error( 'invalid_signature', 'Invalid signature', [ 'status' => 401 ] );
        }
        $params = json_decode( $request->get_body(), true ) ?: [];
        $parsed = ( new CoinsnapProvider() )->handle_webhook( $params );
        if ( ! empty( $parsed['invoice_id'] ) && ! empty( $parsed['paid'] ) ) {
            $res = SyncService::handlePaymentPaid( (string) $parsed['invoice_id'], $params );
            return rest_ensure_response( [ 'ok' => $res['ok'] ] );
        }
        return new \WP_Error( 'invalid', 'Invalid webhook', [ 'status' => 400 ] );
    }

    public static function btcpay_webhook( $request ) {
        if ( ! BTCPayProvider::verify_signature() ) {
            return new \WP_Error( 'invalid_signature', 'Invalid signature', [ 'status' => 401 ] );
        }
        $params = json_decode( $request->get_body(), true ) ?: [];
        $parsed = ( new BTCPayProvider() )->handle_webhook( $params );
        if ( ! empty( $parsed['invoice_id'] ) && ! empty( $parsed['paid'] ) ) {
            $res = SyncService::handlePaymentPaid( (string) $parsed['invoice_id'], $params );
            return rest_ensure_response( [ 'ok' => $res['ok'] ] );
        }
        return new \WP_Error( 'invalid', 'Invalid webhook', [ 'status' => 400 ] );
    }

    public static function status( $request ) {
        $invoice = sanitize_text_field( (string) $request['invoice'] );
        global $wpdb;
        $table = Installer::tableName( $wpdb );
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT payment_status, form_id FROM {$table} WHERE payment_invoice_id=%s", $invoice ), ARRAY_A );
        if ( ! $row ) {
            return rest_ensure_response( [ 'exists' => false ] );
        }
        $welcome    = get_post_meta( (int) $row['form_id'], '_wpbn_email', true );
        $welcomeUrl = is_array( $welcome ) && ! empty( $welcome['welcome_url'] ) ? esc_url_raw( $welcome['welcome_url'] ) : home_url( '/' );
        return rest_ensure_response(
            [
                'exists'   => true,
                'paid'     => $row['payment_status'] === 'paid',
                'redirect' => $row['payment_status'] === 'paid' ? $welcomeUrl : '',
            ]
        );
    }

    public static function resync( $request ) {
        $id = (int) $request['id'];
        $ok = SyncService::resync( $id );
        return rest_ensure_response( [ 'ok' => (bool) $ok ] );
    }

    public static function bulk_resync( $request ) {
        $ids = (array) $request->get_param( 'ids' );
        $ids = array_map( 'absint', $ids );
        $ok  = true;
        foreach ( $ids as $id ) {
            $ok = SyncService::resync( (int) $id ) && $ok;
        }
        return rest_ensure_response( [ 'ok' => $ok ] );
    }
}

