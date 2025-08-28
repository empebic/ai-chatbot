<?php

namespace WpBitcoinNewsletter\Services;

use WpBitcoinNewsletter\Database\Installer;
use WpBitcoinNewsletter\Util\ProviderFactory;
use WpBitcoinNewsletter\Admin\Settings;

defined('ABSPATH') || exit;

class SyncService
{
    public static function handlePaymentPaid(string $invoiceId, array $metadata = []): array
    {
        global $wpdb;
        $table = Installer::tableName($wpdb);
        $subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE payment_invoice_id = %s LIMIT 1", $invoiceId), ARRAY_A);
        if (!$subscriber) {
            return ['ok' => false, 'message' => 'Invoice not found'];
        }
        if ($subscriber['payment_status'] === 'paid') {
            return ['ok' => true, 'message' => 'Already paid'];
        }

        $wpdb->update($table, [
            'payment_status' => 'paid',
            'updated_at' => current_time('mysql'),
        ], [
            'id' => (int)$subscriber['id'],
        ], ['%s', '%s'], ['%d']);

        // Sync to newsletter provider
        $provider = ProviderFactory::newsletter();
        $synced = false;
        $settings = Settings::getSettings();
        $emailMeta = get_post_meta((int)$subscriber['form_id'], '_wpbn_email', true);
        $options = [
            'double_opt_in' => is_array($emailMeta) && !empty($emailMeta['double_opt_in']),
            'mailpoet_list_id' => isset($settings['mailpoet_list_id']) ? (int)$settings['mailpoet_list_id'] : 0,
            'mailchimp_api_key' => isset($settings['mailchimp_api_key']) ? (string)$settings['mailchimp_api_key'] : '',
            'mailchimp_audience_id' => isset($settings['mailchimp_audience_id']) ? (string)$settings['mailchimp_audience_id'] : '',
            'sendinblue_api_key' => isset($settings['sendinblue_api_key']) ? (string)$settings['sendinblue_api_key'] : '',
            'sendinblue_list_id' => isset($settings['sendinblue_list_id']) ? (int)$settings['sendinblue_list_id'] : 0,
            'convertkit_api_secret' => isset($settings['convertkit_api_secret']) ? (string)$settings['convertkit_api_secret'] : '',
            'convertkit_form_id' => isset($settings['convertkit_form_id']) ? (int)$settings['convertkit_form_id'] : 0,
        ];
        try {
            $synced = $provider->upsert([
                'email' => $subscriber['email'],
                'first_name' => $subscriber['first_name'],
                'last_name' => $subscriber['last_name'],
                'phone' => $subscriber['phone'],
                'company' => $subscriber['company'],
                'custom1' => $subscriber['custom1'],
                'custom2' => $subscriber['custom2'],
                'form_id' => (int)$subscriber['form_id'],
            ], $options);
        } catch (\Throwable $e) {
            $synced = false;
        }

        $wpdb->update($table, [
            'provider_sync_status' => $synced ? 'synced' : 'failed',
        ], ['id' => (int)$subscriber['id']], ['%s'], ['%d']);

        self::sendEmails((int)$subscriber['form_id'], $subscriber['email']);

        $welcome = get_post_meta((int)$subscriber['form_id'], '_wpbn_email', true);
        $welcomeUrl = is_array($welcome) && !empty($welcome['welcome_url']) ? esc_url_raw($welcome['welcome_url']) : home_url('/');

        return ['ok' => true, 'redirect' => $welcomeUrl];
    }

    private static function sendEmails(int $formId, string $email): void
    {
        $emailMeta = get_post_meta($formId, '_wpbn_email', true);
        $template = is_array($emailMeta) && !empty($emailMeta['email_template']) ? $emailMeta['email_template'] : __('Thank you for subscribing!', 'wpbn');
        $subject = __('Welcome to our newsletter', 'wpbn');

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        \wp_mail($email, $subject, $template, $headers);

        $adminEmail = get_option('admin_email');
        $adminSubject = sprintf(__('New paid subscription: %s', 'wpbn'), $email);
        $adminBody = sprintf(__('A new subscriber has completed payment on form #%d: %s', 'wpbn'), $formId, $email);
        \wp_mail($adminEmail, $adminSubject, nl2br(esc_html($adminBody)), $headers);
    }
}

