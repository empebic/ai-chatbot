<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

interface NewsletterProviderInterface
{
    /** Create or update a subscriber; return true on success */
    public function upsert(array $subscriber, array $options = []): bool;

    /** Handle unsubscribe if applicable */
    public function unsubscribe(string $email, array $options = []): bool;
}

