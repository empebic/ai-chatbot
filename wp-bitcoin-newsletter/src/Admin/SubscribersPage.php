<?php

namespace WpBitcoinNewsletter\Admin;

use WpBitcoinNewsletter\Database\Installer;

defined('ABSPATH') || exit;

class SubscribersPage
{
    public static function renderPage(): void
    {
        global $wpdb;
        $table = Installer::tableName($wpdb);
        $items = $wpdb->get_results("SELECT id, email, first_name, last_name, payment_status, provider_sync_status, created_at, payment_amount, currency, form_id FROM {$table} ORDER BY id DESC LIMIT 50", ARRAY_A);

        echo '<div class="wrap wpbn-admin">';
        echo '<h1>' . esc_html__('Newsletter Subscribers', 'wpbn') . '</h1>';
        echo '<p>' . esc_html__('Basic view. Advanced filters and actions coming soon.', 'wpbn') . '</p>';
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
}

