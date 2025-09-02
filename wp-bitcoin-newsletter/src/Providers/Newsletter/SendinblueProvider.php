<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sendinblue/Brevo newsletter provider implementation.
 */
class SendinblueProvider implements NewsletterProviderInterface {
    /** @inheritDoc */
    public function upsert( array $subscriber, array $options = [] ): bool {
        $apiKey = (string) ( $options['sendinblue_api_key'] ?? '' );
        $listId = (int) ( $options['sendinblue_list_id'] ?? 0 );
        if ( ! $apiKey || ! $listId ) {
            return false;
        }
        $url     = 'https://api.brevo.com/v3/contacts';
        $payload = [
            'email'      => (string) $subscriber['email'],
            'attributes' => [
                'FIRSTNAME' => (string) ( $subscriber['first_name'] ?? '' ),
                'LASTNAME'  => (string) ( $subscriber['last_name'] ?? '' ),
                'SMS'       => (string) ( $subscriber['phone'] ?? '' ),
                'COMPANY'   => (string) ( $subscriber['company'] ?? '' ),
            ],
            'listIds'       => [ $listId ],
            'updateEnabled' => true,
        ];
        $args = [
            'method'  => 'POST',
            'headers' => [
                'api-key'      => $apiKey,
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

    /** @inheritDoc */
    public function unsubscribe( string $email, array $options = [] ): bool {
        $apiKey = (string) ( $options['sendinblue_api_key'] ?? '' );
        if ( ! $apiKey ) {
            return false;
        }
        $url     = 'https://api.brevo.com/v3/contacts/' . rawurlencode( strtolower( trim( $email ) ) );
        $payload = [ 'unlinkListIds' => [] ];
        $args    = [
            'method'  => 'PUT',
            'headers' => [
                'api-key'      => $apiKey,
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
}

