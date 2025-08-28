<?php

namespace WpBitcoinNewsletter\Rest;

use WpBitcoinNewsletter\Services\SyncService;

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
        $params = $request->get_params();
        $invoiceId = isset($params['invoice_id']) ? (string)$params['invoice_id'] : '';
        $paid = !empty($params['paid']);
        if ($invoiceId && $paid) {
            $res = SyncService::handlePaymentPaid($invoiceId, $params);
            return rest_ensure_response(['ok' => $res['ok']]);
        }
        return new \WP_Error('invalid', 'Invalid webhook', ['status' => 400]);
    }

    public static function btcpayWebhook($request)
    {
        $params = $request->get_params();
        $invoiceId = isset($params['invoice_id']) ? (string)$params['invoice_id'] : '';
        $paid = !empty($params['paid']);
        if ($invoiceId && $paid) {
            $res = SyncService::handlePaymentPaid($invoiceId, $params);
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

