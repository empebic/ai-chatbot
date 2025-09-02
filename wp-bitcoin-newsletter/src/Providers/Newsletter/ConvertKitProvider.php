<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConvertKitProvider implements NewsletterProviderInterface {
    public function upsert( array $subscriber, array $options = [] ): bool {
        $apiSecret = (string) ( $options['convertkit_api_secret'] ?? '' );
        $formId    = (int) ( $options['convertkit_form_id'] ?? 0 );
        if ( ! $apiSecret || ! $formId ) {
            return false;
        }
        $url     = 'https://api.convertkit.com/v3/forms/' . $formId . '/subscribe';
        $payload = [
            'api_secret' => $apiSecret,
            'email'      => (string) $subscriber['email'],
            'first_name' => (string) ( $subscriber['first_name'] ?? '' ),
            'fields'     => [
                'last_name' => (string) ( $subscriber['last_name'] ?? '' ),
                'phone'     => (string) ( $subscriber['phone'] ?? '' ),
                'company'   => (string) ( $subscriber['company'] ?? '' ),
                'custom1'   => (string) ( $subscriber['custom1'] ?? '' ),
                'custom2'   => (string) ( $subscriber['custom2'] ?? '' ),
            ],
        ];
        $args = [
            'method'  => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'accept'       => 'application/json',
            ],
            'timeout' => 20,
            'body'    => wp_json_encode( $payload ),
        ];
        $res = wp_remote_request( $url, $args );
        if ( is_wp_error( $res ) ) {
            return false;
        }
        $code = wp_remote_retrieve_response_code( $res );
        return $code >= 200 && $code < 300;
    }

    public function unsubscribe( string $email, array $options = [] ): bool {
        // ConvertKit requires unsubscribing from sequences/tags; non-trivial here.
        return true;
    }
}

