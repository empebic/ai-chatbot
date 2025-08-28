<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

class DbProvider implements NewsletterProviderInterface
{
    public function upsert(array $subscriber, array $options = []): bool
    {
        // Internal storage already handled by plugin DB. Nothing to do.
        return true;
    }

    public function unsubscribe(string $email, array $options = []): bool
    {
        // Defer to WordPress-level unsubscribe handling if applicable
        return true;
    }
}

