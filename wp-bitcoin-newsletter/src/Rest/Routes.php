<?php

namespace WpBitcoinNewsletter\Rest;

use WpBitcoinNewsletter\Services\SyncService;
use WpBitcoinNewsletter\Providers\Payment\CoinsnapProvider;
use WpBitcoinNewsletter\Providers\Payment\BTCPayProvider;

defined('ABSPATH') || exit;

class Routes
{
    public static function register(): void
    {
        register_rest_route('wpbn/v1', '/payment/coinsnap', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [__CLASS__, 'coinsnapWebhook'],
        ]);

        register_rest_route('wpbn/v1', '/payment/btcpay', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [__CLASS__, 'btcpayWebhook'],
        ]);

        register_rest_route('wpbn/v1', '/subscribers/(?P<id>\\d+)/resync', [
            'methods' => 'POST',
            'permission_callback' => function(){ return current_user_can('manage_options'); },
            'callback' => [__CLASS__, 'resync'],
        ]);
    }

    public static function coinsnapWebhook($request)
    {
        if (!CoinsnapProvider::verifySignature()) {
            return new \WP_Error('invalid_signature', 'Invalid signature', ['status' => 401]);
        }
        $params = json_decode($request->get_body(), true) ?: [];
        $parsed = (new CoinsnapProvider())->handleWebhook($params);
        if (!empty($parsed['invoice_id']) && !empty($parsed['paid'])) {
            $res = SyncService::handlePaymentPaid((string)$parsed['invoice_id'], $params);
            return rest_ensure_response(['ok' => $res['ok']]);
        }
        return new \WP_Error('invalid', 'Invalid webhook', ['status' => 400]);
    }

    public static function btcpayWebhook($request)
    {
        if (!BTCPayProvider::verifySignature()) {
            return new \WP_Error('invalid_signature', 'Invalid signature', ['status' => 401]);
        }
        $params = json_decode($request->get_body(), true) ?: [];
        $parsed = (new BTCPayProvider())->handleWebhook($params);
        if (!empty($parsed['invoice_id']) && !empty($parsed['paid'])) {
            $res = SyncService::handlePaymentPaid((string)$parsed['invoice_id'], $params);
            return rest_ensure_response(['ok' => $res['ok']]);
        }
        return new \WP_Error('invalid', 'Invalid webhook', ['status' => 400]);
    }

    public static function resync($request)
    {
        $id = (int)$request['id'];
        global $wpdb;
        $table = \WpBitcoinNewsletter\Database\Installer::tableName($wpdb);
        $invoiceId = $wpdb->get_var($wpdb->prepare("SELECT payment_invoice_id FROM {$table} WHERE id=%d", $id));
        if (!$invoiceId) return new \WP_Error('not_found', 'Subscriber not found', ['status' => 404]);
        $res = SyncService::handlePaymentPaid((string)$invoiceId);
        return rest_ensure_response(['ok' => $res['ok']]);
    }
}

