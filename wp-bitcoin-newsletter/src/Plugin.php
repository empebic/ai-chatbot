<?php

namespace WpBitcoinNewsletter;

use WpBitcoinNewsletter\CPT\FormPostType;
use WpBitcoinNewsletter\Shortcode\FormShortcode;
use WpBitcoinNewsletter\Admin\Settings as AdminSettings;
use WpBitcoinNewsletter\Admin\SubscribersPage;
use WpBitcoinNewsletter\Rest\Routes as RestRoutes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    /**
     * Singleton instance.
     *
     * @var Plugin|null
     */
    private static $instance;

    public static function instance(): Plugin {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void {
        add_action( 'init', [ FormPostType::class, 'register' ] );
        add_action( 'init', [ FormShortcode::class, 'register' ] );

        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        AdminSettings::register();
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
    }

    public function register_admin_menu(): void {
        add_menu_page(
            __( 'Newsletter Subscribers', 'wpbn' ),
            __( 'Subscribers', 'wpbn' ),
            'manage_options',
            'wpbn-subscribers',
            [ SubscribersPage::class, 'render_page' ],
            'dashicons-email',
            56
        );

        add_submenu_page(
            'wpbn-subscribers',
            __( 'Settings', 'wpbn' ),
            __( 'Settings', 'wpbn' ),
            'manage_options',
            'wpbn-settings',
            [ AdminSettings::class, 'render_page' ]
        );
    }

    public function register_rest_routes(): void {
        RestRoutes::register();
    }

    // Simple test hook to simulate payment success when visiting ?wpbn_invoice=xyz.
    public function simulate_payment(): void {
        if ( ! is_admin() && isset( $_GET['wpbn_invoice'] ) ) {
            $invoiceId = sanitize_text_field( wp_unslash( $_GET['wpbn_invoice'] ) );
            $res       = \WpBitcoinNewsletter\Services\SyncService::handlePaymentPaid( $invoiceId );
            if ( ! headers_sent() && ! empty( $res['redirect'] ) ) {
                wp_safe_redirect( $res['redirect'] );
                exit;
            }
        }
    }

    public function enqueue_frontend(): void {
        wp_register_style( 'wpbn-frontend', WPBN_PLUGIN_URL . 'assets/css/frontend.css', [], WPBN_VERSION );
        wp_register_script( 'wpbn-frontend', WPBN_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery' ], WPBN_VERSION, true );

        wp_localize_script( 'wpbn-frontend', 'WPBN', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => esc_url_raw( get_rest_url( null, 'wpbn/v1/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ] );

        wp_enqueue_style( 'wpbn-frontend' );
        wp_enqueue_script( 'wpbn-frontend' );
    }

    public function enqueue_admin(): void {
        wp_register_style( 'wpbn-admin', WPBN_PLUGIN_URL . 'assets/css/admin.css', [], WPBN_VERSION );
        wp_register_script( 'wpbn-admin', WPBN_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WPBN_VERSION, true );

        wp_enqueue_style( 'wpbn-admin' );
        wp_enqueue_script( 'wpbn-admin' );
    }
}

