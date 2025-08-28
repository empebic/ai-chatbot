<?php

namespace WpBitcoinNewsletter\Providers\Newsletter;

use WpBitcoinNewsletter\Admin\Settings;

class MailchimpProvider implements NewsletterProviderInterface
{
    public function upsert(array $subscriber, array $options = []): bool
    {
        $settings = Settings::getSettings();
        // TODO: Call Mailchimp Marketing API to upsert member
        return true;
    }

    public function unsubscribe(string $email, array $options = []): bool
    {
        // TODO: Call Mailchimp API to unsubscribe
        return true;
    }
}

