<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MailchimpProvider implements NewsletterProviderInterface {
    public function upsert( array $subscriber, array $options = [] ): bool {
        $apiKey     = (string) ( $options['mailchimp_api_key'] ?? '' );
        $audienceId = (string) ( $options['mailchimp_audience_id'] ?? '' );
        if ( ! $apiKey || ! $audienceId ) {
            return false;
        }
        $dc = substr( $apiKey, strpos( $apiKey, '-' ) + 1 );
        if ( ! $dc ) {
            return false;
        }
        $email  = strtolower( trim( (string) $subscriber['email'] ) );
        $hash   = md5( $email );
        $url    = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . rawurlencode( $audienceId ) . '/members/' . $hash;
        $status = ! empty( $options['double_opt_in'] ) ? 'pending' : 'subscribed';
        $body   = [
            'email_address' => $email,
            'status_if_new' => $status,
            'status'        => $status,
            'merge_fields'  => [
                'FNAME' => (string) ( $subscriber['first_name'] ?? '' ),
                'LNAME' => (string) ( $subscriber['last_name'] ?? '' ),
            ],
        ];
        $args = [
            'method'  => 'PUT',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'any:' . $apiKey ),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode( $body ),
        ];
        $res = wp_remote_request( $url, $args );
        if ( is_wp_error( $res ) ) {
            return false;
        }
        $code = wp_remote_retrieve_response_code( $res );
        return $code >= 200 && $code < 300;
    }

    public function unsubscribe( string $email, array $options = [] ): bool {
        $apiKey     = (string) ( $options['mailchimp_api_key'] ?? '' );
        $audienceId = (string) ( $options['mailchimp_audience_id'] ?? '' );
        if ( ! $apiKey || ! $audienceId ) {
            return false;
        }
        $dc = substr( $apiKey, strpos( $apiKey, '-' ) + 1 );
        if ( ! $dc ) {
            return false;
        }
        $emailLower = strtolower( trim( $email ) );
        $hash       = md5( $emailLower );
        $url        = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . rawurlencode( $audienceId ) . '/members/' . $hash;
        $body       = [ 'status' => 'unsubscribed' ];
        $args       = [
            'method'  => 'PATCH',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'any:' . $apiKey ),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode( $body ),
        ];
        $res = wp_remote_request( $url, $args );
        if ( is_wp_error( $res ) ) {
            return false;
        }
        $code = wp_remote_retrieve_response_code( $res );
        return $code >= 200 && $code < 300;
    }
}

