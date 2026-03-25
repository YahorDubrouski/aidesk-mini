<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

/**
 * Stripe Checkout for the Premium one-time product (Price ID from config).
 */
final class PremiumStripeCheckoutService
{
    /**
     * Creates a Stripe Checkout Session and returns the hosted payment URL.
     *
     * @param  array<string, string>  $metadata  Extra Stripe metadata (user_id is always set)
     *
     * @throws \InvalidArgumentException When STRIPE_PREMIUM_PRICE_ID is not set
     * @throws ApiErrorException When Stripe API fails
     */
    public function createCheckoutSession(
        User $user,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): string {
        $this->configureStripeApiKey();

        $priceId = trim((string) config('stripe.premium_price_id'));
        $metadata['user_id'] = (string) $user->id;

        $session = Session::create([
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $user->email,
            'metadata' => $metadata,
        ]);

        return (string) $session->url;
    }

    /**
     * Loads the Checkout Session from Stripe and upserts a paid Order when appropriate.
     *
     * @return Order|null Paid order if session is paid and metadata user exists; null otherwise
     *
     * @throws ApiErrorException When Stripe API fails
     */
    public function fulfillOrderFromCheckoutSession(string $stripeSessionId): ?Order
    {
        $this->configureStripeApiKey();

        $session = Session::retrieve($stripeSessionId, ['expand' => ['payment_intent']]);

        if ($session->payment_status !== 'paid') {
            return null;
        }

        $userId = $session->metadata?->user_id ?? null;
        if (! $userId || ! User::find($userId)) {
            return null;
        }

        $paymentIntentId = null;
        if (isset($session->payment_intent) && is_object($session->payment_intent)) {
            $paymentIntentId = $session->payment_intent->id ?? null;
        }

        $order = Order::query()->updateOrCreate(
            ['stripe_session_id' => $session->id],
            [
                'user_id' => (int) $userId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'status' => Order::STATUS_PAID,
                'amount' => $session->amount_total ?? null,
            ]
        );

        return $order;
    }

    private function configureStripeApiKey(): void
    {
        Stripe::setApiKey(config('stripe.secret'));
    }
}
