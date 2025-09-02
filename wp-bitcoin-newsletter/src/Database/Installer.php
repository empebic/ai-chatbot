<?php
/**
 * DB installer.
 *
 * @package wp-bitcoin-newsletter
 */
declare(strict_types=1);

namespace WpBitcoinNewsletter\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database table installer and utilities.
 */
class Installer {
    /**
     * Get subscribers table name with prefix.
     *
     * @param \wpdb|null $wpdb_param Optional wpdb instance.
     * @return string Table name.
     */
    public static function table_name( $wpdb_param = null ): string {
        global $wpdb;
        $db = $wpdb_param ? $wpdb_param : $wpdb;
        return $db->prefix . \WpBitcoinNewsletter\Constants::SUBSCRIBERS_TABLE_SUFFIX;
    }

    /**
     * Back-compat alias for camelCase method.
     *
     * @deprecated 0.2.0 Use table_name() instead.
     * @phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
     */
    public static function tableName( $wpdbParam = null ): string { // phpcs:ignore Squiz.NamingConventions.ValidFunctionName.NotCamelCaps
        return self::table_name( $wpdbParam );
    }

    /**
     * Activation callback to create/update DB schema.
     */
    public static function activate(): void {
        global $wpdb;
        $table          = self::tableName( $wpdb );
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            first_name VARCHAR(190) NULL,
            last_name VARCHAR(190) NULL,
            phone VARCHAR(190) NULL,
            company VARCHAR(190) NULL,
            custom1 TEXT NULL,
            custom2 TEXT NULL,
            gdpr_consent TINYINT(1) NOT NULL DEFAULT 0,
            ip VARCHAR(64) NULL,
            user_agent TEXT NULL,
            payment_provider VARCHAR(50) NULL,
            payment_invoice_id VARCHAR(190) NULL,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            provider_sync_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_amount BIGINT NULL,
            currency VARCHAR(10) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY form_id (form_id),
            KEY email (email),
            KEY payment_status (payment_status),
            KEY provider_sync_status (provider_sync_status)
        ) $charset_collate;";

        \dbDelta( $sql );
    }
}

