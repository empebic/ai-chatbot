<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class SubscriptionController extends Controller
{
    public function create(Request $request, string $username): View
    {
        $profile = Profile::where('username', $username)->with('user')->firstOrFail();
        abort_unless($profile->is_creator, 404);
        abort_if($request->user()->id === $profile->user_id, 403);

        return view('subscriptions.create', [
            'profile' => $profile,
        ]);
    }

    public function store(Request $request, string $username): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:yes'],
        ]);

        $profile = Profile::where('username', $username)->with('user')->firstOrFail();
        abort_unless($profile->is_creator, 404);
        abort_if($request->user()->id === $profile->user_id, 403);

        if (empty(config('services.stripe.secret'))) {
            abort(500, 'Stripe is not configured');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $amountCents = (int) round(($profile->monthly_price ?? 0) * 100);
        if ($amountCents <= 0) {
            abort(400, 'Creator has no price set');
        }

        $successUrl = URL::temporarySignedRoute('feed', now()->addMinutes(30));
        $cancelUrl = route('creator.show', $profile->username);

        $session = StripeCheckoutSession::create([
            'mode' => 'subscription',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($profile->currency ?? 'usd'),
                    'product_data' => [
                        'name' => 'Subscription to @'.$profile->username,
                    ],
                    'recurring' => [
                        'interval' => 'month',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'success_url' => $successUrl.
                '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'subscriber_id' => (string) $request->user()->id,
                'creator_id' => (string) $profile->user_id,
            ],
        ]);

        return redirect()->away($session->url);
    }
}
