<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Internal DB provider (no external sync required).
 */
class DbProvider implements NewsletterProviderInterface {
    /** @inheritDoc */
    public function upsert( array $subscriber, array $options = [] ): bool {
        // Internal storage already handled by plugin DB. Nothing to do.
        return true;
    }

    /** @inheritDoc */
    public function unsubscribe( string $email, array $options = [] ): bool {
        // Defer to WordPress-level unsubscribe handling if applicable.
        return true;
    }
}

