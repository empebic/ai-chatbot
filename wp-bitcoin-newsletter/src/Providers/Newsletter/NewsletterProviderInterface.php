<?php
declare(strict_types=1);
/**
 * Newsletter provider interface.
 *
 * @package wp-bitcoin-newsletter
 */

namespace WpBitcoinNewsletter\Providers\Newsletter;

/**
 * Newsletter providers must support upsert and unsubscribe.
 */
interface NewsletterProviderInterface {
    /**
     * Create or update a subscriber.
     *
     * @param array $subscriber Subscriber fields.
     * @param array $options    Provider-specific options.
     * @return bool True on success.
     */
    public function upsert( array $subscriber, array $options = [] ): bool;

    /**
     * Unsubscribe an email address.
     *
     * @param string $email   Email address.
     * @param array  $options Provider-specific options.
     * @return bool True on success.
     */
    public function unsubscribe( string $email, array $options = [] ): bool;
}

