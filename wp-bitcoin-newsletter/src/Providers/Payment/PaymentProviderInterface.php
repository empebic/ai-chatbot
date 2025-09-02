<?php
/**
 * Payment provider interface.
 *
 * @package wp-bitcoin-newsletter
 */
declare(strict_types=1);

namespace WpBitcoinNewsletter\Providers\Payment;

/**
 * Payment providers must implement invoice creation and webhook handling.
 */
interface PaymentProviderInterface {
    /**
     * Create a payment invoice.
     *
     * @param int    $formId         Form ID.
     * @param int    $amount         Amount.
     * @param string $currency       Currency code.
     * @param array  $subscriberData Subscriber data.
     * @return array { invoice_id: string, payment_url: string, expires_at?: int }
     */
    /**
     * Create a payment invoice.
     *
     * @param int    $formId         Form ID.
     * @param int    $amount         Amount.
     * @param string $currency       Currency code.
     * @param array  $subscriberData Subscriber data.
     * @return array { invoice_id: string, payment_url: string, expires_at?: int }
     */
    public function create_invoice( int $formId, int $amount, string $currency, array $subscriberData ): array;

    /**
     * Validate webhook or callback.
     *
     * @param array $request Parsed request body.
     * @return array { invoice_id: string, paid: bool, metadata: array }
     */
    /**
     * Validate webhook or callback.
     *
     * @param array $request Parsed request body.
     * @return array { invoice_id: string, paid: bool, metadata: array }
     */
    public function handle_webhook( array $request ): array;
}

