<?php

namespace WpBitcoinNewsletter\Util;

use WpBitcoinNewsletter\Admin\Settings;
use WpBitcoinNewsletter\Providers\Payment\PaymentProviderInterface;
use WpBitcoinNewsletter\Providers\Payment\CoinsnapProvider;
use WpBitcoinNewsletter\Providers\Payment\BTCPayProvider;
use WpBitcoinNewsletter\Providers\Newsletter\NewsletterProviderInterface;
use WpBitcoinNewsletter\Providers\Newsletter\DbProvider;
use WpBitcoinNewsletter\Providers\Newsletter\MailPoetProvider;
use WpBitcoinNewsletter\Providers\Newsletter\MailchimpProvider;
use WpBitcoinNewsletter\Providers\Newsletter\SendinblueProvider;
use WpBitcoinNewsletter\Providers\Newsletter\ConvertKitProvider;

class ProviderFactory {
    public static function paymentForForm( int $formId ): PaymentProviderInterface {
        $settings = Settings::getSettings();
        $payment  = get_post_meta( $formId, '_wpbn_payment', true );
        $override = is_array( $payment ) && ! empty( $payment['provider_override'] ) ? $payment['provider_override'] : '';
        $key      = $override ?: $settings['payment_provider'];
        switch ( $key ) {
            case 'btcpay':
                return new BTCPayProvider();
            case 'coinsnap':
            default:
                return new CoinsnapProvider();
        }
    }

    public static function newsletter( int $formId = 0 ): NewsletterProviderInterface {
        $settings     = Settings::getSettings();
        $providerMeta = $formId ? get_post_meta( $formId, '_wpbn_provider', true ) : [];
        $override     = is_array( $providerMeta ) && ! empty( $providerMeta['provider_override'] ) ? $providerMeta['provider_override'] : '';
        $key          = $override ?: $settings['newsletter_provider'];
        switch ( $key ) {
            case 'mailpoet':
                return new MailPoetProvider();
            case 'mailchimp':
                return new MailchimpProvider();
            case 'sendinblue':
                return new SendinblueProvider();
            case 'convertkit':
                return new ConvertKitProvider();
            case 'wpdb':
            default:
                return new DbProvider();
        }
    }
}

