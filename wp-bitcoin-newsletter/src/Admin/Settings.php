<?php

namespace WpBitcoinNewsletter\Admin;

defined('ABSPATH') || exit;

class Settings
{
    public const OPTION_KEY = 'wpbn_settings';

    public static function register(): void
    {
        add_action('admin_init', [__CLASS__, 'registerSettings']);
    }

    public static function getSettings(): array
    {
        $defaults = [
            'payment_provider' => 'coinsnap',
            'default_amount' => 21,
            'default_currency' => 'SATS',
            'coinsnap_api_key' => '',
            'btcpay_host' => '',
            'btcpay_api_key' => '',
            'newsletter_provider' => 'wpdb',
            'mailpoet_list_id' => '',
            'mailchimp_api_key' => '',
            'mailchimp_audience_id' => '',
            'sendinblue_api_key' => '',
            'sendinblue_list_id' => '',
            'convertkit_api_secret' => '',
            'convertkit_form_id' => '',
        ];
        $opts = get_option(self::OPTION_KEY, []);
        if (!is_array($opts)) $opts = [];
        return array_merge($defaults, $opts);
    }

    public static function registerSettings(): void
    {
        register_setting('wpbn_settings_group', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
        ]);

        add_settings_section('wpbn_general', __('General', 'wpbn'), function () {
            echo '<p>' . esc_html__('Configure payment and newsletter providers.', 'wpbn') . '</p>';
        }, 'wpbn-settings');

        add_settings_field('payment_provider', __('Default Payment Provider', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<select name="' . esc_attr(self::OPTION_KEY) . '[payment_provider]">';
            foreach ([
                'coinsnap' => 'Coinsnap',
                'btcpay' => 'BTCPay',
            ] as $k => $label) {
                echo '<option value="' . esc_attr($k) . '" ' . selected($k, $s['payment_provider'], false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }, 'wpbn-settings', 'wpbn_general');

        add_settings_field('default_amount', __('Default Amount', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="number" min="1" name="' . esc_attr(self::OPTION_KEY) . '[default_amount]" value="' . esc_attr((string)$s['default_amount']) . '" />';
        }, 'wpbn-settings', 'wpbn_general');

        add_settings_field('default_currency', __('Default Currency', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<select name="' . esc_attr(self::OPTION_KEY) . '[default_currency]">';
            foreach (['SATS','USD','EUR','CHF','JPY'] as $cur) {
                echo '<option value="' . esc_attr($cur) . '" ' . selected($cur, $s['default_currency'], false) . '>' . esc_html($cur) . '</option>';
            }
            echo '</select>';
        }, 'wpbn-settings', 'wpbn_general');

        add_settings_section('wpbn_coinsnap', __('Coinsnap', 'wpbn'), function () {}, 'wpbn-settings');
        add_settings_field('coinsnap_api_key', __('Coinsnap API Key', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[coinsnap_api_key]" value="' . esc_attr($s['coinsnap_api_key']) . '" />';
        }, 'wpbn-settings', 'wpbn_coinsnap');

        add_settings_section('wpbn_btcpay', __('BTCPay Server', 'wpbn'), function () {}, 'wpbn-settings');
        add_settings_field('btcpay_host', __('BTCPay Host', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="url" class="regular-text" placeholder="https://your-btcpay.example" name="' . esc_attr(self::OPTION_KEY) . '[btcpay_host]" value="' . esc_attr($s['btcpay_host']) . '" />';
        }, 'wpbn-settings', 'wpbn_btcpay');
        add_settings_field('btcpay_api_key', __('BTCPay API Key', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[btcpay_api_key]" value="' . esc_attr($s['btcpay_api_key']) . '" />';
        }, 'wpbn-settings', 'wpbn_btcpay');

        add_settings_section('wpbn_news', __('Newsletter Provider', 'wpbn'), function () {}, 'wpbn-settings');
        add_settings_field('newsletter_provider', __('Provider', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<select name="' . esc_attr(self::OPTION_KEY) . '[newsletter_provider]">';
            foreach ([
                'wpdb' => __('WP Database (internal only)', 'wpbn'),
                'mailpoet' => 'MailPoet',
                'mailchimp' => 'Mailchimp',
                'sendinblue' => 'Sendinblue/Brevo',
                'convertkit' => 'ConvertKit',
            ] as $k => $label) {
                echo '<option value="' . esc_attr($k) . '" ' . selected($k, $s['newsletter_provider'], false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
        }, 'wpbn-settings', 'wpbn_news');

        add_settings_field('mailpoet_list_id', __('MailPoet List ID', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[mailpoet_list_id]" value="' . esc_attr($s['mailpoet_list_id']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');
        add_settings_field('mailchimp_api_key', __('Mailchimp API Key', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[mailchimp_api_key]" value="' . esc_attr($s['mailchimp_api_key']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');
        add_settings_field('mailchimp_audience_id', __('Mailchimp Audience ID', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[mailchimp_audience_id]" value="' . esc_attr($s['mailchimp_audience_id']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');

        add_settings_field('sendinblue_api_key', __('Sendinblue API Key', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[sendinblue_api_key]" value="' . esc_attr($s['sendinblue_api_key']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');
        add_settings_field('sendinblue_list_id', __('Sendinblue List ID', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[sendinblue_list_id]" value="' . esc_attr($s['sendinblue_list_id']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');

        add_settings_field('convertkit_api_secret', __('ConvertKit API Secret', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[convertkit_api_secret]" value="' . esc_attr($s['convertkit_api_secret']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');
        add_settings_field('convertkit_form_id', __('ConvertKit Form ID', 'wpbn'), function () {
            $s = self::getSettings();
            echo '<input type="text" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[convertkit_form_id]" value="' . esc_attr($s['convertkit_form_id']) . '" />';
        }, 'wpbn-settings', 'wpbn_news');
    }

    public static function sanitize($input)
    {
        if (!is_array($input)) return [];
        $out = [];
        $out['payment_provider'] = isset($input['payment_provider']) ? sanitize_text_field($input['payment_provider']) : 'coinsnap';
        $out['default_amount'] = isset($input['default_amount']) ? absint($input['default_amount']) : 21;
        $out['default_currency'] = isset($input['default_currency']) ? sanitize_text_field($input['default_currency']) : 'SATS';
        $out['coinsnap_api_key'] = isset($input['coinsnap_api_key']) ? sanitize_text_field($input['coinsnap_api_key']) : '';
        $out['btcpay_host'] = isset($input['btcpay_host']) ? esc_url_raw($input['btcpay_host']) : '';
        $out['btcpay_api_key'] = isset($input['btcpay_api_key']) ? sanitize_text_field($input['btcpay_api_key']) : '';
        $out['newsletter_provider'] = isset($input['newsletter_provider']) ? sanitize_text_field($input['newsletter_provider']) : 'wpdb';
        $out['mailpoet_list_id'] = isset($input['mailpoet_list_id']) ? sanitize_text_field($input['mailpoet_list_id']) : '';
        $out['mailchimp_api_key'] = isset($input['mailchimp_api_key']) ? sanitize_text_field($input['mailchimp_api_key']) : '';
        $out['mailchimp_audience_id'] = isset($input['mailchimp_audience_id']) ? sanitize_text_field($input['mailchimp_audience_id']) : '';
        $out['sendinblue_api_key'] = isset($input['sendinblue_api_key']) ? sanitize_text_field($input['sendinblue_api_key']) : '';
        $out['sendinblue_list_id'] = isset($input['sendinblue_list_id']) ? sanitize_text_field($input['sendinblue_list_id']) : '';
        $out['convertkit_api_secret'] = isset($input['convertkit_api_secret']) ? sanitize_text_field($input['convertkit_api_secret']) : '';
        $out['convertkit_form_id'] = isset($input['convertkit_form_id']) ? sanitize_text_field($input['convertkit_form_id']) : '';
        return $out;
    }

    public static function renderPage(): void
    {
        echo '<div class="wrap wpbn-admin">';
        echo '<h1>' . esc_html__('WP Bitcoin Newsletter Settings', 'wpbn') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('wpbn_settings_group');
        do_settings_sections('wpbn-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

