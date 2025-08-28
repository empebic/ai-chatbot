<?php

namespace WpBitcoinNewsletter\Shortcode;

use WpBitcoinNewsletter\CPT\FormPostType;
use WpBitcoinNewsletter\Database\Installer;
use WpBitcoinNewsletter\Util\ProviderFactory;

defined('ABSPATH') || exit;

class FormShortcode
{
    public static function register(): void
    {
        add_shortcode('coinsnap_newsletter_form', [__CLASS__, 'render']);
        add_action('wp_ajax_wpbn_submit_form', [__CLASS__, 'handleSubmit']);
        add_action('wp_ajax_nopriv_wpbn_submit_form', [__CLASS__, 'handleSubmit']);
    }

    public static function render($atts): string
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'coinsnap_newsletter_form');

        $postId = absint($atts['id']);
        if (!$postId || get_post_type($postId) !== FormPostType::POST_TYPE) {
            return '';
        }

        $fields = get_post_meta($postId, '_wpbn_fields', true);
        if (!is_array($fields)) {
            $fields = [];
        }
        $gdpr = get_post_meta($postId, '_wpbn_gdpr', true);
        if (!is_array($gdpr)) {
            $gdpr = [];
        }
        $gdprText = isset($gdpr['gdpr_text']) && $gdpr['gdpr_text'] ? $gdpr['gdpr_text'] : __('I consent to receive newsletters and accept the privacy policy.', 'wpbn');
        $privacyUrl = isset($gdpr['privacy_policy_url']) ? esc_url($gdpr['privacy_policy_url']) : '';

        $nonce = wp_create_nonce('wpbn_form_' . $postId);
        $honeypotName = 'website';

        $ordered = self::buildOrderedFields($fields);

        ob_start();
        echo '<form class="wpbn-form" method="post" action="' . esc_url(admin_url('admin-ajax.php')) . '" data-form-id="' . esc_attr($postId) . '">';
        echo '<input type="hidden" name="action" value="wpbn_submit_form" />';
        echo '<input type="hidden" name="wpbn_form_id" value="' . esc_attr($postId) . '" />';
        echo '<input type="hidden" name="wpbn_nonce" value="' . esc_attr($nonce) . '" />';
        echo '<div style="position:absolute;left:-9999px;top:-9999px;"><label>' . esc_html__('Do not fill this out', 'wpbn') . ' <input type="text" name="' . esc_attr($honeypotName) . '" value="" /></label></div>';

        // Email (always required)
        $emailLabel = isset($fields['email_label']) ? $fields['email_label'] : __('Email Address', 'wpbn');
        echo '<p class="wpbn-field"><label>' . esc_html($emailLabel) . ' <span class="required">*</span><br /><input type="email" name="email" required /></label></p>';

        foreach ($ordered as $field) {
            echo self::renderField($field);
        }

        echo '<p class="wpbn-gdpr"><label><input type="checkbox" name="gdpr_consent" value="1" required /> ' . wp_kses_post($gdprText);
        if ($privacyUrl) {
            echo ' <a href="' . esc_url($privacyUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Privacy Policy', 'wpbn') . '</a>';
        }
        echo '</label></p>';

        echo '<p><button type="submit" class="wpbn-submit">' . esc_html__('Continue to Payment', 'wpbn') . '</button></p>';
        echo '</form>';
        return (string)ob_get_clean();
    }

    private static function buildOrderedFields(array $fields): array
    {
        $map = [];
        $candidates = [
            'first_name' => __('First Name', 'wpbn'),
            'last_name' => __('Last Name', 'wpbn'),
            'phone' => __('Phone Number', 'wpbn'),
            'company' => __('Company', 'wpbn'),
            'custom1' => __('Custom Field 1', 'wpbn'),
            'custom2' => __('Custom Field 2', 'wpbn'),
        ];
        foreach ($candidates as $slug => $fallbackLabel) {
            $enabled = isset($fields[$slug . '_enabled']) && $fields[$slug . '_enabled'];
            if (!$enabled) {
                continue;
            }
            $map[] = [
                'slug' => $slug,
                'label' => isset($fields[$slug . '_label']) && $fields[$slug . '_label'] ? $fields[$slug . '_label'] : $fallbackLabel,
                'required' => !empty($fields[$slug . '_required']),
                'order' => isset($fields[$slug . '_order']) ? (int)$fields[$slug . '_order'] : 100,
                'type' => isset($fields[$slug . '_type']) ? $fields[$slug . '_type'] : 'text',
                'options' => isset($fields[$slug . '_options']) ? $fields[$slug . '_options'] : '',
            ];
        }
        usort($map, function ($a, $b) {
            return ($a['order'] <=> $b['order']);
        });
        return $map;
    }

    private static function renderField(array $field): string
    {
        $required = $field['required'] ? ' required' : '';
        $requiredMark = $field['required'] ? ' <span class="required">*</span>' : '';
        $name = esc_attr($field['slug']);
        $label = esc_html($field['label']);

        if (in_array($field['slug'], ['custom1', 'custom2'], true) && $field['type'] === 'select') {
            $options = array_filter(array_map('trim', explode(',', (string)$field['options'])));
            $html = '<p class="wpbn-field"><label>' . $label . $requiredMark . '<br /><select name="' . $name . '"' . $required . '>';
            foreach ($options as $opt) {
                $html .= '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>';
            }
            $html .= '</select></label></p>';
            return $html;
        }

        return '<p class="wpbn-field"><label>' . $label . $requiredMark . '<br /><input type="text" name="' . $name . '"' . $required . ' /></label></p>';
    }

    public static function handleSubmit(): void
    {
        \check_ajax_referer('wpbn_form_' . (isset($_POST['wpbn_form_id']) ? absint($_POST['wpbn_form_id']) : 0), 'wpbn_nonce');

        $formId = isset($_POST['wpbn_form_id']) ? absint($_POST['wpbn_form_id']) : 0;
        if (!$formId || get_post_type($formId) !== FormPostType::POST_TYPE) {
            wp_send_json_error(['message' => __('Invalid form.', 'wpbn')], 400);
        }
        if (!empty($_POST['website'])) {
            wp_send_json_error(['message' => __('Spam detected.', 'wpbn')], 400);
        }

        // Simple IP rate limit: 1 request per 10 seconds per IP per form
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $key = 'wpbn_rl_' . md5($formId . '|' . $ip);
        if (get_transient($key)) {
            wp_send_json_error(['message' => __('Please wait a few seconds before trying again.', 'wpbn')], 429);
        }
        set_transient($key, 1, 10);

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (!$email || !is_email($email)) {
            wp_send_json_error(['message' => __('Please provide a valid email address.', 'wpbn')], 422);
        }

        $fields = get_post_meta($formId, '_wpbn_fields', true);
        if (!is_array($fields)) {
            $fields = [];
        }

        $data = [
            'email' => $email,
            'first_name' => isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '',
            'last_name' => isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            'company' => isset($_POST['company']) ? sanitize_text_field(wp_unslash($_POST['company'])) : '',
            'custom1' => isset($_POST['custom1']) ? sanitize_text_field(wp_unslash($_POST['custom1'])) : '',
            'custom2' => isset($_POST['custom2']) ? sanitize_text_field(wp_unslash($_POST['custom2'])) : '',
            'gdpr_consent' => !empty($_POST['gdpr_consent']) ? 1 : 0,
        ];

        if (!$data['gdpr_consent']) {
            wp_send_json_error(['message' => __('GDPR consent is required.', 'wpbn')], 422);
        }

        global $wpdb;
        $table = Installer::tableName($wpdb);
        $inserted = $wpdb->insert(
            $table,
            [
                'form_id' => $formId,
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'company' => $data['company'],
                'custom1' => $data['custom1'],
                'custom2' => $data['custom2'],
                'gdpr_consent' => $data['gdpr_consent'],
                'ip' => $ip,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_textarea_field($_SERVER['HTTP_USER_AGENT']) : '',
                'payment_status' => 'unpaid',
                'provider_sync_status' => 'pending',
            ],
            [
                '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s'
            ]
        );

        if (!$inserted) {
            wp_send_json_error(['message' => __('Could not create subscriber.', 'wpbn')], 500);
        }

        $subscriberId = (int)$wpdb->insert_id;

        // Determine amount/currency and create invoice via selected provider
        $payment = get_post_meta($formId, '_wpbn_payment', true);
        if (!is_array($payment)) $payment = [];
        $amount = isset($payment['amount']) ? absint($payment['amount']) : 21;
        $currency = isset($payment['currency']) ? sanitize_text_field($payment['currency']) : 'SATS';

        $provider = ProviderFactory::paymentForForm($formId);
        $invoice = $provider->createInvoice($formId, $amount, $currency, $data);
        $invoiceId = isset($invoice['invoice_id']) ? (string)$invoice['invoice_id'] : '';
        $paymentUrl = isset($invoice['payment_url']) ? (string)$invoice['payment_url'] : '';

        if (!$invoiceId || !$paymentUrl) {
            wp_send_json_error(['message' => __('Failed to create invoice.', 'wpbn')], 500);
        }

        $wpdb->update(
            $table,
            [
                'payment_invoice_id' => $invoiceId,
                'payment_provider' => is_object($provider) ? strtolower((new \ReflectionClass($provider))->getShortName()) : '',
                'payment_amount' => $amount,
                'currency' => $currency,
            ],
            ['id' => $subscriberId],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );

        wp_send_json_success([
            'invoice_id' => $invoiceId,
            'payment_url' => add_query_arg(['wpbn_invoice' => $invoiceId], $paymentUrl),
            'subscriber_id' => $subscriberId,
        ]);
    }
}

