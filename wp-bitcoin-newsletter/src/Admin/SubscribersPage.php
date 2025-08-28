<?php

namespace WpBitcoinNewsletter\Admin;

use WpBitcoinNewsletter\Database\Installer;

defined('ABSPATH') || exit;

class SubscribersPage
{
    public static function renderPage(): void
    {
        if (isset($_GET['wpbn_export']) && current_user_can('manage_options')) {
            self::exportCsv();
            return;
        }

        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $formId = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;

        global $wpdb;
        $table = Installer::tableName($wpdb);
        $where = [];
        $params = [];
        if ($status) { $where[] = 'payment_status = %s'; $params[] = $status; }
        if ($formId) { $where[] = 'form_id = %d'; $params[] = $formId; }
        $sql = "SELECT id, email, first_name, last_name, payment_status, provider_sync_status, created_at, payment_amount, currency, form_id FROM {$table}";
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY id DESC LIMIT 200';
        $items = $params ? $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) : $wpdb->get_results($sql, ARRAY_A);

        echo '<div class="wrap wpbn-admin">';
        echo '<h1>' . esc_html__('Newsletter Subscribers', 'wpbn') . '</h1>';

        echo '<form method="get" style="margin:10px 0;">';
        echo '<input type="hidden" name="page" value="wpbn-subscribers" />';
        echo '<label>' . esc_html__('Status', 'wpbn') . ': <select name="status">';
        foreach (['' => __('All', 'wpbn'), 'paid' => 'paid', 'unpaid' => 'unpaid'] as $k => $label) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($k, $status, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label> ';
        echo '<label>' . esc_html__('Form ID', 'wpbn') . ': <input type="number" name="form_id" value="' . ($formId ? (int)$formId : '') . '" /></label> ';
        submit_button(__('Filter', 'wpbn'), 'secondary', '', false);
        echo ' <a class="button" href="' . esc_url(add_query_arg(['wpbn_export' => 1])) . '">' . esc_html__('Export CSV', 'wpbn') . '</a>';
        echo '</form>';

        echo '<table class="widefat striped"><thead><tr>';
        $cols = ['ID','Email','Name','Form ID','Payment Status','Provider Sync','Amount','Date'];
        foreach ($cols as $c) echo '<th>' . esc_html($c) . '</th>';
        echo '</tr></thead><tbody>';
        if ($items) {
            foreach ($items as $row) {
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                $amount = $row['payment_amount'] ? esc_html($row['payment_amount'] . ' ' . $row['currency']) : '';
                echo '<tr>';
                echo '<td>' . esc_html((string)$row['id']) . '</td>';
                echo '<td>' . esc_html((string)$row['email']) . '</td>';
                echo '<td>' . esc_html($name) . '</td>';
                echo '<td>' . esc_html((string)$row['form_id']) . '</td>';
                echo '<td>' . esc_html((string)$row['payment_status']) . '</td>';
                echo '<td>' . esc_html((string)$row['provider_sync_status']) . '</td>';
                echo '<td>' . $amount . '</td>';
                echo '<td>' . esc_html((string)$row['created_at']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8">' . esc_html__('No subscribers found.', 'wpbn') . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    private static function exportCsv(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wpbn'));
        }
        global $wpdb;
        $table = Installer::tableName($wpdb);
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wpbn-subscribers.csv');
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
        }
        fclose($out);
        exit;
    }
}

