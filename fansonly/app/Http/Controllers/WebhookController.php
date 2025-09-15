<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Stripe\Event as StripeEvent;
use Stripe\Webhook as StripeWebhook;

class WebhookController extends Controller
{
    public function handleStripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = $secret
                ? StripeWebhook::constructEvent($payload, $sigHeader, $secret)
                : StripeEvent::constructFrom(json_decode($payload, true));
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return Response::make('Invalid', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $data = $event->data->object;
                $subscriberId = (int) ($data->metadata->subscriber_id ?? 0);
                $creatorId = (int) ($data->metadata->creator_id ?? 0);
                if ($subscriberId && $creatorId) {
                    Subscription::updateOrCreate(
                        [
                            'subscriber_id' => $subscriberId,
                            'creator_id' => $creatorId,
                        ],
                        [
                            'status' => ($data->status ?? 'active'),
                            'stripe_subscription_id' => $data->id ?? null,
                            'current_period_start' => isset($data->current_period_start) ? date('c', $data->current_period_start) : null,
                            'current_period_end' => isset($data->current_period_end) ? date('c', $data->current_period_end) : null,
                            'amount' => isset($data->plan->amount) ? $data->plan->amount / 100 : 0,
                            'currency' => strtoupper($data->plan->currency ?? 'USD'),
                        ]
                    );
                }
                break;
            case 'customer.subscription.deleted':
                $data = $event->data->object;
                $subscriberId = (int) ($data->metadata->subscriber_id ?? 0);
                $creatorId = (int) ($data->metadata->creator_id ?? 0);
                if ($subscriberId && $creatorId) {
                    Subscription::where('subscriber_id', $subscriberId)
                        ->where('creator_id', $creatorId)
                        ->update(['status' => 'canceled', 'canceled_at' => now()]);
                }
                break;
        }

        return Response::make('OK', 200);
    }
}
