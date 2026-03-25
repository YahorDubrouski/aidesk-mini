<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Stripe;

use App\Http\Controllers\Api\Controller;
use App\Services\Payment\PremiumStripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly PremiumStripeCheckoutService $premiumStripeCheckout
    ) {}

    /**
     * Handle Stripe webhook events. Verifies signature and processes checkout.session.completed.
     */
    #[OA\Post(
        path: '/stripe/webhook',
        description: 'Called by Stripe with signed payloads. Not for interactive Bearer auth — use `Stripe-Signature` header. Configure `STRIPE_WEBHOOK_SECRET`.',
        summary: 'Stripe webhook',
        requestBody: new OA\RequestBody(
            description: 'Raw Stripe event JSON (exact bytes from Stripe)',
            required: true,
            content: new OA\JsonContent
        ),
        tags: ['Stripe Webhooks'],
        parameters: [
            new OA\Parameter(
                name: 'Stripe-Signature',
                description: 'Stripe signing header',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Event handled or ignored (empty body)'),
            new OA\Response(
                response: 400,
                description: 'Missing webhook secret, invalid signature, or invalid payload (plain text body)'
            ),
        ]
    )]
    public function __invoke(Request $request): Response
    {
        $secret = config('stripe.webhook_secret');
        if (empty($secret)) {
            return response('Webhook secret not configured', 400);
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException) {
            return response('Invalid payload', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $sessionId = $event->data->object->id ?? null;
            if ($sessionId) {
                $this->premiumStripeCheckout->fulfillOrderFromCheckoutSession($sessionId);
            }
        }

        return response('', 200);
    }
}
