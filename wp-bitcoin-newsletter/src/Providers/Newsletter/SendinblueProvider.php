<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

use WpBitcoinNewsletter\Admin\Settings;

class SendinblueProvider implements NewsletterProviderInterface
{
    public function upsert(array $subscriber, array $options = []): bool
    {
        $settings = Settings::getSettings();
        // TODO: Integrate with Brevo/Sendinblue API
        return true;
    }

    public function unsubscribe(string $email, array $options = []): bool
    {
        return true;
    }
}

