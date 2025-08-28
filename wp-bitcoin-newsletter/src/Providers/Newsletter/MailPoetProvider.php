<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

class MailPoetProvider implements NewsletterProviderInterface
{
    public function upsert(array $subscriber, array $options = []): bool
    {
        // TODO: Integrate with MailPoet APIs
        return true;
    }

    public function unsubscribe(string $email, array $options = []): bool
    {
        return true;
    }
}

