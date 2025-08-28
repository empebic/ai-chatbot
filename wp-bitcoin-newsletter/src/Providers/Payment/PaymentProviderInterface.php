<?php

namespace WpBitcoinNewsletter\Providers\Payment;

interface PaymentProviderInterface
{
    /**
     * Create a payment invoice and return an array containing keys:
     * - invoice_id (string)
     * - payment_url (string)
     * - expires_at (int timestamp) optional
     */
    public function createInvoice(int $formId, int $amount, string $currency, array $subscriberData): array;

    /** Validate webhook or callback and return ['invoice_id' => string, 'paid' => bool, 'metadata' => array] */
    public function handleWebhook(array $request): array;
}

