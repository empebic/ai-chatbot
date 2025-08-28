<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

use WpBitcoinNewsletter\Admin\Settings;

class ConvertKitProvider implements NewsletterProviderInterface
{
    public function upsert(array $subscriber, array $options = []): bool
    {
        $settings = Settings::getSettings();
        // TODO: Integrate with ConvertKit API
        return true;
    }

    public function unsubscribe(string $email, array $options = []): bool
    {
        return true;
    }
}

