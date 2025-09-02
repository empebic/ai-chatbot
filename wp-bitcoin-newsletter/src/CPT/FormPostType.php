<?php

namespace WpBitcoinNewsletter\CPT;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FormPostType {
    public const POST_TYPE = 'coinsnap_newsletter';

    public static function register(): void {
        $labels = [
            'name'               => __( 'Newsletter Forms', 'wpbn' ),
            'singular_name'      => __( 'Newsletter Form', 'wpbn' ),
            'add_new'            => __( 'Add New', 'wpbn' ),
            'add_new_item'       => __( 'Add New Newsletter Form', 'wpbn' ),
            'edit_item'          => __( 'Edit Newsletter Form', 'wpbn' ),
            'new_item'           => __( 'New Newsletter Form', 'wpbn' ),
            'all_items'          => __( 'Newsletter Forms', 'wpbn' ),
            'view_item'          => __( 'View Newsletter Form', 'wpbn' ),
            'search_items'       => __( 'Search Newsletter Forms', 'wpbn' ),
            'not_found'          => __( 'No forms found', 'wpbn' ),
            'not_found_in_trash' => __( 'No forms found in Trash', 'wpbn' ),
            'menu_name'          => __( 'Newsletter Forms', 'wpbn' ),
        ];

        register_post_type(
            self::POST_TYPE,
            [
                'labels'       => $labels,
                'public'       => false,
                'show_ui'      => true,
                'show_in_menu' => 'wpbn-subscribers',
                'menu_position'=> 57,
                'menu_icon'    => 'dashicons-feedback',
                'supports'     => [ 'title' ],
                'has_archive'  => false,
                'show_in_rest' => false,
            ]
        );

        add_action( 'add_meta_boxes', [ __CLASS__, 'register_metaboxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save_meta' ], 10, 2 );
    }

    public static function register_metaboxes(): void {
        add_meta_box( 'wpbn_fields', __( 'Form Fields', 'wpbn' ), [ __CLASS__, 'render_fields_metabox' ], self::POST_TYPE, 'normal' );
        add_meta_box( 'wpbn_payment', __( 'Payment Settings', 'wpbn' ), [ __CLASS__, 'render_payment_metabox' ], self::POST_TYPE, 'side' );
        add_meta_box( 'wpbn_email', __( 'Email & Redirect', 'wpbn' ), [ __CLASS__, 'render_email_metabox' ], self::POST_TYPE, 'normal' );
        add_meta_box( 'wpbn_gdpr', __( 'GDPR & Compliance', 'wpbn' ), [ __CLASS__, 'render_gdpr_metabox' ], self::POST_TYPE, 'normal' );
        add_meta_box( 'wpbn_provider', __( 'Newsletter Provider (Override)', 'wpbn' ), [ __CLASS__, 'render_provider_metabox' ], self::POST_TYPE, 'side' );
        add_meta_box( 'wpbn_shortcode', __( 'Shortcode', 'wpbn' ), [ __CLASS__, 'render_shortcode_metabox' ], self::POST_TYPE, 'side' );
    }

    public static function render_fields_metabox( \WP_Post $post ): void {
        wp_nonce_field( 'wpbn_save_form_' . $post->ID, 'wpbn_form_nonce' );

        $defaults = [
            'email_label'         => __( 'Email Address', 'wpbn' ),
            'first_name_enabled'  => '1',
            'first_name_required' => '0',
            'first_name_label'    => __( 'First Name', 'wpbn' ),
            'first_name_order'    => '20',
            'last_name_enabled'   => '1',
            'last_name_required'  => '0',
            'last_name_label'     => __( 'Last Name', 'wpbn' ),
            'last_name_order'     => '30',
            'phone_enabled'       => '0',
            'phone_required'      => '0',
            'phone_label'         => __( 'Phone Number', 'wpbn' ),
            'phone_order'         => '40',
            'company_enabled'     => '0',
            'company_required'    => '0',
            'company_label'       => __( 'Company', 'wpbn' ),
            'company_order'       => '50',
            'custom1_enabled'     => '0',
            'custom1_required'    => '0',
            'custom1_label'       => __( 'Custom Field 1', 'wpbn' ),
            'custom1_type'        => 'text',
            'custom1_options'     => '',
            'custom1_order'       => '60',
            'custom2_enabled'     => '0',
            'custom2_required'    => '0',
            'custom2_label'       => __( 'Custom Field 2', 'wpbn' ),
            'custom2_type'        => 'text',
            'custom2_options'     => '',
            'custom2_order'       => '70',
        ];
        $values = get_post_meta( $post->ID, '_wpbn_fields', true );
        if ( ! is_array( $values ) ) {
            $values = [];
        }
        $values = array_merge( $defaults, $values );

        echo '<p><label>' . esc_html__( 'Email Label', 'wpbn' ) . '</label><br />';
        echo '<input type="text" class="widefat" name="wpbn_fields[email_label]" value="' . esc_attr( $values['email_label'] ) . '" /></p>';

        self::render_toggle_row( 'first_name', $values );
        self::render_toggle_row( 'last_name', $values );
        self::render_toggle_row( 'phone', $values );
        self::render_toggle_row( 'company', $values );
        self::render_custom_row( 'custom1', $values );
        self::render_custom_row( 'custom2', $values );
    }

    private static function render_toggle_row( string $slug, array $values ): void {
        echo '<fieldset style="border:1px solid #ddd;padding:10px;margin:10px 0;">';
        echo '<legend><strong>' . esc_html( ucwords( str_replace( '_', ' ', $slug ) ) ) . '</strong></legend>';
        echo '<label><input type="checkbox" name="wpbn_fields[' . esc_attr( $slug ) . '_enabled]" value="1" ' . checked( '1', $values[ $slug . '_enabled' ], false ) . ' /> ' . esc_html__( 'Enabled', 'wpbn' ) . '</label><br />';
        echo '<label><input type="checkbox" name="wpbn_fields[' . esc_attr( $slug ) . '_required]" value="1" ' . checked( '1', $values[ $slug . '_required' ], false ) . ' /> ' . esc_html__( 'Required', 'wpbn' ) . '</label><br />';
        echo '<label>' . esc_html__( 'Label', 'wpbn' ) . ': <input type="text" name="wpbn_fields[' . esc_attr( $slug ) . '_label]" value="' . esc_attr( $values[ $slug . '_label' ] ) . '" /></label><br />';
        echo '<label>' . esc_html__( 'Order', 'wpbn' ) . ': <input type="number" name="wpbn_fields[' . esc_attr( $slug ) . '_order]" value="' . esc_attr( $values[ $slug . '_order' ] ) . '" /></label>';
        echo '</fieldset>';
    }

    private static function render_custom_row( string $slug, array $values ): void {
        echo '<fieldset style="border:1px solid #ddd;padding:10px;margin:10px 0;">';
        echo '<legend><strong>' . esc_html( ucwords( str_replace( '_', ' ', $slug ) ) ) . '</strong></legend>';
        echo '<label><input type="checkbox" name="wpbn_fields[' . esc_attr( $slug ) . '_enabled]" value="1" ' . checked( '1', $values[ $slug . '_enabled' ], false ) . ' /> ' . esc_html__( 'Enabled', 'wpbn' ) . '</label><br />';
        echo '<label><input type="checkbox" name="wpbn_fields[' . esc_attr( $slug ) . '_required]" value="1" ' . checked( '1', $values[ $slug . '_required' ], false ) . ' /> ' . esc_html__( 'Required', 'wpbn' ) . '</label><br />';
        echo '<label>' . esc_html__( 'Label', 'wpbn' ) . ': <input type="text" name="wpbn_fields[' . esc_attr( $slug ) . '_label]" value="' . esc_attr( $values[ $slug . '_label' ] ) . '" /></label><br />';
        echo '<label>' . esc_html__( 'Type', 'wpbn' ) . ': <select name="wpbn_fields[' . esc_attr( $slug ) . '_type]">';
        foreach ( [ 'text' => __( 'Text', 'wpbn' ), 'select' => __( 'Select', 'wpbn' ) ] as $k => $label ) {
            echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $values[ $slug . '_type' ], false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></label><br />';
        echo '<label>' . esc_html__( 'Options (comma-separated, for select)', 'wpbn' ) . ': <input type="text" class="widefat" name="wpbn_fields[' . esc_attr( $slug ) . '_options]" value="' . esc_attr( $values[ $slug . '_options' ] ) . '" /></label><br />';
        echo '<label>' . esc_html__( 'Order', 'wpbn' ) . ': <input type="number" name="wpbn_fields[' . esc_attr( $slug ) . '_order]" value="' . esc_attr( $values[ $slug . '_order' ] ) . '" /></label>';
        echo '</fieldset>';
    }

    public static function render_payment_metabox( \WP_Post $post ): void {
        $defaults = [
            'amount'            => '21',
            'currency'          => 'SATS',
            'provider_override' => '',
        ];
        $values = get_post_meta( $post->ID, '_wpbn_payment', true );
        if ( ! is_array( $values ) ) {
            $values = [];
        }
        $values = array_merge( $defaults, $values );

        echo '<p><label>' . esc_html__( 'Amount', 'wpbn' ) . ': <input type="number" min="1" name="wpbn_payment[amount]" value="' . esc_attr( $values['amount'] ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'Currency', 'wpbn' ) . ': <select name="wpbn_payment[currency]">';
        foreach ( [ 'SATS', 'USD', 'EUR', 'CHF', 'JPY' ] as $cur ) {
            echo '<option value="' . esc_attr( $cur ) . '" ' . selected( $cur, $values['currency'], false ) . '>' . esc_html( $cur ) . '</option>';
        }
        echo '</select></label></p>';

        echo '<p><label>' . esc_html__( 'Override Payment Provider (global default otherwise)', 'wpbn' ) . ': <select name="wpbn_payment[provider_override]">';
        foreach ( [ '' => __( 'Use Global Default', 'wpbn' ), 'coinsnap' => 'Coinsnap', 'btcpay' => 'BTCPay' ] as $k => $label ) {
            echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $values['provider_override'], false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></label></p>';
    }

    public static function render_email_metabox( \WP_Post $post ): void {
        $defaults = [
            'welcome_url'      => '',
            'email_template'   => '',
            'double_opt_in'    => '0',
            'unsubscribe_page' => '',
        ];
        $values = get_post_meta( $post->ID, '_wpbn_email', true );
        if ( ! is_array( $values ) ) {
            $values = [];
        }
        $values = array_merge( $defaults, $values );

        echo '<p><label>' . esc_html__( 'Welcome Page URL', 'wpbn' ) . '<br /><input type="url" class="widefat" name="wpbn_email[welcome_url]" value="' . esc_attr( $values['welcome_url'] ) . '" /></label></p>';
        echo '<p><label><input type="checkbox" name="wpbn_email[double_opt_in]" value="1" ' . checked( '1', $values['double_opt_in'], false ) . ' /> ' . esc_html__( 'Enable Double Opt-in (if provider supports)', 'wpbn' ) . '</label></p>';
        echo '<p><label>' . esc_html__( 'Unsubscribe Page URL', 'wpbn' ) . '<br /><input type="url" class="widefat" name="wpbn_email[unsubscribe_page]" value="' . esc_attr( $values['unsubscribe_page'] ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'Confirmation Email Template (HTML allowed)', 'wpbn' ) . '<br />';
        echo '<textarea class="widefat" rows="6" name="wpbn_email[email_template]">' . esc_textarea( $values['email_template'] ) . '</textarea></label></p>';
    }

    public static function render_gdpr_metabox( \WP_Post $post ): void {
        $defaults = [
            'gdpr_text'          => __( 'I consent to receive newsletters and accept the privacy policy.', 'wpbn' ),
            'retention_days'     => '0',
            'privacy_policy_url' => '',
        ];
        $values = get_post_meta( $post->ID, '_wpbn_gdpr', true );
        if ( ! is_array( $values ) ) {
            $values = [];
        }
        $values = array_merge( $defaults, $values );

        echo '<p><label>' . esc_html__( 'GDPR Consent Text', 'wpbn' ) . '<br />';
        echo '<textarea class="widefat" rows="4" name="wpbn_gdpr[gdpr_text]">' . esc_textarea( $values['gdpr_text'] ) . '</textarea></label></p>';
        echo '<p><label>' . esc_html__( 'Privacy Policy URL', 'wpbn' ) . '<br /><input type="url" class="widefat" name="wpbn_gdpr[privacy_policy_url]" value="' . esc_attr( $values['privacy_policy_url'] ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'Data Retention (days, 0 = keep indefinitely)', 'wpbn' ) . ': <input type="number" min="0" name="wpbn_gdpr[retention_days]" value="' . esc_attr( $values['retention_days'] ) . '" /></label></p>';
    }

    public static function render_provider_metabox( \WP_Post $post ): void {
        $defaults = [
            'provider_override'     => '',
            'mailpoet_list_id'      => '',
            'mailchimp_audience_id' => '',
            'sendinblue_list_id'    => '',
            'convertkit_form_id'    => '',
        ];
        $values = get_post_meta( $post->ID, '_wpbn_provider', true );
        if ( ! is_array( $values ) ) {
            $values = [];
        }
        $values = array_merge( $defaults, $values );

        echo '<p><label>' . esc_html__( 'Override Newsletter Provider (global default otherwise)', 'wpbn' ) . ': <select name="wpbn_provider[provider_override]">';
        foreach ( [
            ''         => __( 'Use Global Default', 'wpbn' ),
            'wpdb'     => __( 'WP Database (internal only)', 'wpbn' ),
            'mailpoet' => 'MailPoet',
            'mailchimp'=> 'Mailchimp',
            'sendinblue' => 'Sendinblue/Brevo',
            'convertkit' => 'ConvertKit',
        ] as $k => $label ) {
            echo '<option value="' . esc_attr( $k ) . '" ' . selected( $k, $values['provider_override'], false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select></label></p>';

        echo '<p><label>' . esc_html__( 'MailPoet List ID (override)', 'wpbn' ) . '<br /><input type="text" class="widefat" name="wpbn_provider[mailpoet_list_id]" value="' . esc_attr( $values['mailpoet_list_id'] ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'Mailchimp Audience ID (override)', 'wpbn' ) . '<br /><input type="text" class="widefat" name="wpbn_provider[mailchimp_audience_id]" value="' . esc_attr( $values['mailchimp_audience_id'] ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'Sendinblue List ID (override)', 'wpbn' ) . '<br /><input type="text" class="widefat" name="wpbn_provider[sendinblue_list_id]" value="' . esc_attr( $values['sendinblue_list_id'] ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'ConvertKit Form ID (override)', 'wpbn' ) . '<br /><input type="text" class="widefat" name="wpbn_provider[convertkit_form_id]" value="' . esc_attr( $values['convertkit_form_id'] ) . '" /></label></p>';
    }

    public static function render_shortcode_metabox( \WP_Post $post ): void {
        $shortcode = '[coinsnap_newsletter_form id="' . (int) $post->ID . '"]';
        echo '<input type="text" class="widefat" readonly value="' . esc_attr( $shortcode ) . '" onclick="this.select();" />';
    }

    public static function save_meta( int $postId, \WP_Post $post ): void {
        if ( ! isset( $_POST['wpbn_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpbn_form_nonce'] ) ), 'wpbn_save_form_' . $postId ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $postId ) ) {
            return;
        }

        $fields   = isset( $_POST['wpbn_fields'] ) && is_array( $_POST['wpbn_fields'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['wpbn_fields'] ) ) : [];
        $payment  = isset( $_POST['wpbn_payment'] ) && is_array( $_POST['wpbn_payment'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['wpbn_payment'] ) ) : [];
        $email    = isset( $_POST['wpbn_email'] ) && is_array( $_POST['wpbn_email'] ) ? wp_unslash( $_POST['wpbn_email'] ) : [];
        $gdpr     = isset( $_POST['wpbn_gdpr'] ) && is_array( $_POST['wpbn_gdpr'] ) ? wp_unslash( $_POST['wpbn_gdpr'] ) : [];
        $provider = isset( $_POST['wpbn_provider'] ) && is_array( $_POST['wpbn_provider'] ) ? wp_unslash( $_POST['wpbn_provider'] ) : [];

        // Sanitize more complex fields.
        $email['email_template']   = isset( $email['email_template'] ) ? wp_kses_post( $email['email_template'] ) : '';
        $email['welcome_url']      = isset( $email['welcome_url'] ) ? esc_url_raw( $email['welcome_url'] ) : '';
        $email['unsubscribe_page'] = isset( $email['unsubscribe_page'] ) ? esc_url_raw( $email['unsubscribe_page'] ) : '';
        $email['double_opt_in']    = isset( $email['double_opt_in'] ) && $email['double_opt_in'] ? '1' : '0';

        $gdpr['gdpr_text']          = isset( $gdpr['gdpr_text'] ) ? wp_kses_post( $gdpr['gdpr_text'] ) : '';
        $gdpr['privacy_policy_url'] = isset( $gdpr['privacy_policy_url'] ) ? esc_url_raw( $gdpr['privacy_policy_url'] ) : '';
        $gdpr['retention_days']     = isset( $gdpr['retention_days'] ) ? absint( $gdpr['retention_days'] ) : 0;

        $provider['provider_override']     = isset( $provider['provider_override'] ) ? sanitize_text_field( $provider['provider_override'] ) : '';
        $provider['mailpoet_list_id']      = isset( $provider['mailpoet_list_id'] ) ? sanitize_text_field( $provider['mailpoet_list_id'] ) : '';
        $provider['mailchimp_audience_id'] = isset( $provider['mailchimp_audience_id'] ) ? sanitize_text_field( $provider['mailchimp_audience_id'] ) : '';
        $provider['sendinblue_list_id']    = isset( $provider['sendinblue_list_id'] ) ? sanitize_text_field( $provider['sendinblue_list_id'] ) : '';
        $provider['convertkit_form_id']    = isset( $provider['convertkit_form_id'] ) ? sanitize_text_field( $provider['convertkit_form_id'] ) : '';

        update_post_meta( $postId, '_wpbn_fields', $fields );
        update_post_meta( $postId, '_wpbn_payment', $payment );
        update_post_meta( $postId, '_wpbn_email', $email );
        update_post_meta( $postId, '_wpbn_gdpr', $gdpr );
        update_post_meta( $postId, '_wpbn_provider', $provider );
    }
}

