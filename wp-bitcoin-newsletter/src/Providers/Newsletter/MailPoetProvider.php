<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * MailPoet newsletter provider implementation.
 */
class MailPoetProvider implements NewsletterProviderInterface {
    /** @inheritDoc */
    public function upsert( array $subscriber, array $options = [] ): bool {
        if ( ! class_exists( 'MailPoet\\API\\API' ) ) {
            return false;
        }
        $listId = isset( $options['mailpoet_list_id'] ) ? (int) $options['mailpoet_list_id'] : 0;
        if ( ! $listId ) {
            return false;
        }

        $api    = \MailPoet\API\API::MP( 'v1' );
        $status = ! empty( $options['double_opt_in'] ) ? 'unconfirmed' : 'subscribed';
        $data   = [
            'email'      => (string) $subscriber['email'],
            'first_name' => (string) ( $subscriber['first_name'] ?? '' ),
            'last_name'  => (string) ( $subscriber['last_name'] ?? '' ),
            'status'     => $status,
        ];
        try {
            $api->addSubscriber( $data, [ $listId ] );
            return true;
        } catch ( \Throwable $e ) {
            // Try update if exists.
            try {
                $existing = $api->getSubscriber( $data['email'] );
                if ( $existing && ! empty( $existing['id'] ) ) {
                    $api->subscribeToList( $existing['id'], $listId );
                    if ( $status === 'subscribed' ) {
                        $api->subscribe( $existing['id'] );
                    }
                    return true;
                }
            } catch ( \Throwable $e2 ) {}
        }
        return false;
    }

    /** @inheritDoc */
    public function unsubscribe( string $email, array $options = [] ): bool {
        if ( ! class_exists( 'MailPoet\\API\\API' ) ) {
            return false;
        }
        try {
            $api = \MailPoet\API\API::MP( 'v1' );
            $sub = $api->getSubscriber( $email );
            if ( $sub && ! empty( $sub['id'] ) ) {
                $api->unsubscribe( $sub['id'] );
                return true;
            }
        } catch ( \Throwable $e ) {}
        return false;
    }
}

