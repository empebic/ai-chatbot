<?php
declare(strict_types=1);
/**
 * Constants container.
 *
 * @package wp-bitcoin-newsletter
 */

namespace WpBitcoinNewsletter;

/**
 * Shared constants for endpoints and namespaces.
 */
class Constants {
    /** REST namespace for the plugin. */
    public const REST_NAMESPACE = 'wpbn/v1';
    public const REST_ROUTE_PAYMENT_COINSNAP = '/payment/coinsnap';
    public const REST_ROUTE_PAYMENT_BTCPAY = '/payment/btcpay';
    public const REST_ROUTE_STATUS = '/status/(?P<invoice>[^/]+)';
    public const REST_ROUTE_RESYNC = '/subscribers/(?P<id>\\d+)/resync';
    public const REST_ROUTE_BULK_RESYNC = '/subscribers/bulk-resync';

    /** CoinSnap endpoints (relative to API base). */
    public const COINSNAP_DEFAULT_API_BASE = 'https://api.coinsnap.io';
    public const COINSNAP_INVOICES_ENDPOINT_V1 = '/api/v1/stores/%s/invoices';
    public const COINSNAP_INVOICES_ENDPOINT_ALT = '/api/stores/%s/invoices';

    /** CoinSnap API header names. */
    public const COINSNAP_HEADER_API_KEY = 'X-Api-Key';

    /** BTCPay endpoint (relative to host). */
    public const BTCPAY_INVOICES_ENDPOINT = '/api/v1/stores/%s/invoices';

    /** ConvertKit endpoint. */
    public const CONVERTKIT_SUBSCRIBE_ENDPOINT = 'https://api.convertkit.com/v3/forms/%d/subscribe';

    /** Mailchimp base and endpoints. */
    public const MAILCHIMP_BASE = 'https://%s.api.mailchimp.com/3.0';
    public const MAILCHIMP_MEMBER_ENDPOINT = '/lists/%s/members/%s';

    /** Sendinblue/Brevo base and endpoints. */
    public const SENDINBLUE_BASE = 'https://api.brevo.com/v3';
    public const SENDINBLUE_CONTACTS = '/contacts';
    public const SENDINBLUE_CONTACT = '/contacts/%s';

    /** DB table suffixes. */
    public const SUBSCRIBERS_TABLE_SUFFIX = 'wpbn_subscribers';
}

