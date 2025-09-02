<?php
/**
 * Synchronization services for payment and newsletter providers.
 *
 * @package wp-bitcoin-newsletter
 */
declare(strict_types=1);

namespace WpBitcoinNewsletter\Services;

use WpBitcoinNewsletter\Database\Installer;
use WpBitcoinNewsletter\Util\ProviderFactory;
use WpBitcoinNewsletter\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SyncService {
    /**
     * Handle payment marked as paid by provider.
     *
     * @param string $invoice_id Invoice identifier.
     * @param array  $metadata   Optional metadata from webhook.
     * @return array { ok: bool, redirect?: string, message?: string }
     */
    public static function handle_payment_paid( string $invoice_id, array $metadata = [] ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
        global $wpdb;
        $table      = Installer::tableName( $wpdb );
        // Table name is trusted, values are placeholders.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE payment_invoice_id = %s LIMIT 1", $invoice_id ), ARRAY_A );
        if ( ! $subscriber ) {
            return [ 'ok' => false, 'message' => 'Invoice not found' ];
        }
        if ( 'paid' === $subscriber['payment_status'] ) {
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

        do_action( 'wpbn_payment_marked_paid', $invoice_id, $subscriber );

        self::resync( (int) $subscriber['id'] );

        $welcome     = get_post_meta( (int) $subscriber['form_id'], '_wpbn_email', true );
        $welcome_url = is_array( $welcome ) && ! empty( $welcome['welcome_url'] ) ? esc_url_raw( $welcome['welcome_url'] ) : home_url( '/' );

        return [ 'ok' => true, 'redirect' => $welcome_url ];
    }

    /**
     * Resync a subscriber to the configured newsletter provider.
     *
     * @param int $subscriber_id Subscriber ID.
     * @return bool True on success.
     */
    public static function resync( int $subscriber_id ): bool {
        global $wpdb;
        $table      = Installer::tableName( $wpdb );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $subscriber_id ), ARRAY_A );
        if ( ! $subscriber ) {
            return false;
        }
        $form_id = (int) $subscriber['form_id'];

        $provider     = ProviderFactory::newsletter( $form_id );
        $settings     = Settings::getSettings();
        $email_meta   = get_post_meta( $form_id, '_wpbn_email', true );
        $provider_meta = get_post_meta( $form_id, '_wpbn_provider', true );
        $options     = [
            'double_opt_in'         => is_array( $email_meta ) && ! empty( $email_meta['double_opt_in'] ),
            'mailpoet_list_id'      => isset( $provider_meta['mailpoet_list_id'] ) && '' !== $provider_meta['mailpoet_list_id'] ? (int) $provider_meta['mailpoet_list_id'] : (int) ( $settings['mailpoet_list_id'] ?? 0 ),
            'mailchimp_api_key'     => (string) ( $settings['mailchimp_api_key'] ?? '' ),
            'mailchimp_audience_id' => isset( $provider_meta['mailchimp_audience_id'] ) && '' !== $provider_meta['mailchimp_audience_id'] ? (string) $provider_meta['mailchimp_audience_id'] : (string) ( $settings['mailchimp_audience_id'] ?? '' ),
            'sendinblue_api_key'    => (string) ( $settings['sendinblue_api_key'] ?? '' ),
            'sendinblue_list_id'    => isset( $provider_meta['sendinblue_list_id'] ) && '' !== $provider_meta['sendinblue_list_id'] ? (int) $provider_meta['sendinblue_list_id'] : (int) ( $settings['sendinblue_list_id'] ?? 0 ),
            'convertkit_api_secret' => (string) ( $settings['convertkit_api_secret'] ?? '' ),
            'convertkit_form_id'    => isset( $provider_meta['convertkit_form_id'] ) && '' !== $provider_meta['convertkit_form_id'] ? (int) $provider_meta['convertkit_form_id'] : (int) ( $settings['convertkit_form_id'] ?? 0 ),
        ];
        $synced = false;
        try {
            do_action( 'wpbn_before_provider_sync', $subscriber_id, $subscriber, $options );
            $synced = $provider->upsert(
                [
                    'email'      => $subscriber['email'],
                    'first_name' => $subscriber['first_name'],
                    'last_name'  => $subscriber['last_name'],
                    'phone'      => $subscriber['phone'],
                    'company'    => $subscriber['company'],
                    'custom1'    => $subscriber['custom1'],
                    'custom2'    => $subscriber['custom2'],
                    'form_id'    => $form_id,
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
            [ 'id' => $subscriber_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        do_action( 'wpbn_after_provider_sync', $subscriber_id, (bool) $synced );

        if ( $synced ) {
            self::send_emails( $form_id, $subscriber['email'] );
        }
        return $synced;
    }

    /**
     * Send welcome email to subscriber and admin notification.
     *
     * @param int    $form_id Form ID.
     * @param string $email  Subscriber email.
     */
    private static function send_emails( int $form_id, string $email ): void {
        $email_meta = get_post_meta( $form_id, '_wpbn_email', true );
        $template  = is_array( $email_meta ) && ! empty( $email_meta['email_template'] ) ? $email_meta['email_template'] : __( 'Thank you for subscribing!', 'wpbn' );
        $subject   = __( 'Welcome to our newsletter', 'wpbn' );

        $headers  = [ 'Content-Type: text/html; charset=UTF-8' ];
        $template = apply_filters( 'wpbn_welcome_email_template', $template, $form_id, $email );
        $subject  = apply_filters( 'wpbn_welcome_email_subject', $subject, $form_id, $email );

        \wp_mail( $email, $subject, $template, $headers );
        do_action( 'wpbn_welcome_email_sent', $email, $form_id );

        $admin_email   = get_option( 'admin_email' );
        /* translators: %s email address */
        $admin_subject = sprintf( __( 'New paid subscription: %s', 'wpbn' ), $email );
        /* translators: 1: form id, 2: email */
        $admin_body    = sprintf( __( 'A new subscriber has completed payment on form #%d: %s', 'wpbn' ), $form_id, $email );
        \wp_mail( $admin_email, $admin_subject, nl2br( esc_html( $admin_body ) ), $headers );
        do_action( 'wpbn_admin_notification_sent', $email, $form_id );
    }
}

