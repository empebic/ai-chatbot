<?php

namespace WpBitcoinNewsletter\Services;

use WpBitcoinNewsletter\Database\Installer;
use WpBitcoinNewsletter\Util\ProviderFactory;
use WpBitcoinNewsletter\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Synchronization services for payment and newsletter providers.
 */
class SyncService {
    /**
     * Handle payment marked as paid by provider.
     *
     * @param string $invoiceId Invoice identifier.
     * @param array  $metadata  Optional metadata from webhook.
     * @return array { ok: bool, redirect?: string, message?: string }
     */
    public static function handlePaymentPaid( string $invoiceId, array $metadata = [] ): array {
        global $wpdb;
        $table      = Installer::tableName( $wpdb );
        $subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE payment_invoice_id = %s LIMIT 1", $invoiceId ), ARRAY_A );
        if ( ! $subscriber ) {
            return [ 'ok' => false, 'message' => 'Invoice not found' ];
        }
        if ( $subscriber['payment_status'] === 'paid' ) {
            // Already paid; still ensure provider sync.
            self::resync( (int) $subscriber['id'] );
            return [ 'ok' => true, 'message' => 'Already paid' ];
        }

        $wpdb->update(
            $table,
            [
                'payment_status' => 'paid',
                'updated_at'     => current_time( 'mysql' ),
            ],
            [
                'id' => (int) $subscriber['id'],
            ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        do_action( 'wpbn_payment_marked_paid', $invoiceId, $subscriber );

        self::resync( (int) $subscriber['id'] );

        $welcome    = get_post_meta( (int) $subscriber['form_id'], '_wpbn_email', true );
        $welcomeUrl = is_array( $welcome ) && ! empty( $welcome['welcome_url'] ) ? esc_url_raw( $welcome['welcome_url'] ) : home_url( '/' );

        return [ 'ok' => true, 'redirect' => $welcomeUrl ];
    }

    /**
     * Resync a subscriber to the configured newsletter provider.
     *
     * @param int $subscriberId Subscriber ID.
     * @return bool True on success.
     */
    public static function resync( int $subscriberId ): bool {
        global $wpdb;
        $table      = Installer::tableName( $wpdb );
        $subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $subscriberId ), ARRAY_A );
        if ( ! $subscriber ) {
            return false;
        }
        $formId = (int) $subscriber['form_id'];

        $provider    = ProviderFactory::newsletter( $formId );
        $settings    = Settings::getSettings();
        $emailMeta   = get_post_meta( $formId, '_wpbn_email', true );
        $providerMeta = get_post_meta( $formId, '_wpbn_provider', true );
        $options     = [
            'double_opt_in'         => is_array( $emailMeta ) && ! empty( $emailMeta['double_opt_in'] ),
            'mailpoet_list_id'      => isset( $providerMeta['mailpoet_list_id'] ) && $providerMeta['mailpoet_list_id'] !== '' ? (int) $providerMeta['mailpoet_list_id'] : (int) ( $settings['mailpoet_list_id'] ?? 0 ),
            'mailchimp_api_key'     => (string) ( $settings['mailchimp_api_key'] ?? '' ),
            'mailchimp_audience_id' => isset( $providerMeta['mailchimp_audience_id'] ) && $providerMeta['mailchimp_audience_id'] !== '' ? (string) $providerMeta['mailchimp_audience_id'] : (string) ( $settings['mailchimp_audience_id'] ?? '' ),
            'sendinblue_api_key'    => (string) ( $settings['sendinblue_api_key'] ?? '' ),
            'sendinblue_list_id'    => isset( $providerMeta['sendinblue_list_id'] ) && $providerMeta['sendinblue_list_id'] !== '' ? (int) $providerMeta['sendinblue_list_id'] : (int) ( $settings['sendinblue_list_id'] ?? 0 ),
            'convertkit_api_secret' => (string) ( $settings['convertkit_api_secret'] ?? '' ),
            'convertkit_form_id'    => isset( $providerMeta['convertkit_form_id'] ) && $providerMeta['convertkit_form_id'] !== '' ? (int) $providerMeta['convertkit_form_id'] : (int) ( $settings['convertkit_form_id'] ?? 0 ),
        ];
        $synced = false;
        try {
            do_action( 'wpbn_before_provider_sync', $subscriberId, $subscriber, $options );
            $synced = $provider->upsert(
                [
                    'email'      => $subscriber['email'],
                    'first_name' => $subscriber['first_name'],
                    'last_name'  => $subscriber['last_name'],
                    'phone'      => $subscriber['phone'],
                    'company'    => $subscriber['company'],
                    'custom1'    => $subscriber['custom1'],
                    'custom2'    => $subscriber['custom2'],
                    'form_id'    => $formId,
                ],
                $options
            );
        } catch ( \Throwable $e ) {
            $synced = false;
        }

        $wpdb->update(
            $table,
            [
                'provider_sync_status' => $synced ? 'synced' : 'failed',
                'updated_at'           => current_time( 'mysql' ),
            ],
            [ 'id' => $subscriberId ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        do_action( 'wpbn_after_provider_sync', $subscriberId, (bool) $synced );

        if ( $synced ) {
            self::send_emails( $formId, $subscriber['email'] );
        }
        return $synced;
    }

    /**
     * Send welcome email to subscriber and admin notification.
     *
     * @param int    $formId Form ID.
     * @param string $email  Subscriber email.
     */
    private static function send_emails( int $formId, string $email ): void {
        $emailMeta = get_post_meta( $formId, '_wpbn_email', true );
        $template  = is_array( $emailMeta ) && ! empty( $emailMeta['email_template'] ) ? $emailMeta['email_template'] : __( 'Thank you for subscribing!', 'wpbn' );
        $subject   = __( 'Welcome to our newsletter', 'wpbn' );

        $headers  = [ 'Content-Type: text/html; charset=UTF-8' ];
        $template = apply_filters( 'wpbn_welcome_email_template', $template, $formId, $email );
        $subject  = apply_filters( 'wpbn_welcome_email_subject', $subject, $formId, $email );

        \wp_mail( $email, $subject, $template, $headers );
        do_action( 'wpbn_welcome_email_sent', $email, $formId );

        $adminEmail   = get_option( 'admin_email' );
        $adminSubject = sprintf( __( 'New paid subscription: %s', 'wpbn' ), $email );
        $adminBody    = sprintf( __( 'A new subscriber has completed payment on form #%d: %s', 'wpbn' ), $formId, $email );
        \wp_mail( $adminEmail, $adminSubject, nl2br( esc_html( $adminBody ) ), $headers );
        do_action( 'wpbn_admin_notification_sent', $email, $formId );
    }
}

