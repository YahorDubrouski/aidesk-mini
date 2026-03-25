<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Api\Controller;
use App\Models\User;
use App\Services\Payment\PremiumStripeCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenApi\Attributes as OA;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly PremiumStripeCheckoutService $premiumStripeCheckout
    ) {}

    /**
     * Create a Premium Checkout Session. Returns JSON with checkout_url, or redirects if redirect=1.
     */
    #[OA\Post(
        path: '/checkout/premium',
        description: 'Returns JSON with `checkout_url`, or HTTP redirect to Stripe when `redirect=1` (query). Requires Sanctum.',
        summary: 'Create Stripe Premium Checkout session',
        security: [['sanctum' => []]],
        tags: ['Checkout'],
        parameters: [
            new OA\Parameter(
                name: 'redirect',
                description: 'If true, respond with 302 to Stripe Checkout URL instead of JSON',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: false)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Checkout URL (JSON)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'checkout_url',
                            type: 'string',
                            format: 'uri',
                            example: 'https://checkout.stripe.com/c/pay/cs_test_...'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 302,
                description: 'Redirect to Stripe Checkout when `redirect=1` (see `Location` header)'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
                    ]
                )
            ),
        ]
    )]
    public function createPremiumSession(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $urls = $this->premiumStripeReturnUrls();

        $checkoutUrl = $this->premiumStripeCheckout->createCheckoutSession(
            $user,
            $urls['success'],
            $urls['cancel']
        );

        if ($request->boolean('redirect')) {
            return redirect()->away($checkoutUrl);
        }

        return response()->json(['checkout_url' => $checkoutUrl]);
    }

    /**
     * Web session: create Premium Checkout Session and redirect the browser to Stripe Checkout.
     */
    public function redirectToPremiumCheckout(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $urls = $this->premiumStripeReturnUrls();

        $checkoutUrl = $this->premiumStripeCheckout->createCheckoutSession(
            $user,
            $urls['success'],
            $urls['cancel']
        );

        return redirect()->away($checkoutUrl);
    }

    /**
     * Success page after payment (Stripe redirects here with ?session_id=cs_xxx).
     */
    public function showCheckoutSuccess(Request $request): View
    {
        $order = null;
        $sessionId = $request->query('session_id');
        if ($sessionId) {
            $order = $this->premiumStripeCheckout->fulfillOrderFromCheckoutSession($sessionId);
        }

        return view('checkout.success', ['order' => $order]);
    }

    /**
     * Cancel page when user abandons checkout.
     */
    public function showCheckoutCancel(): View
    {
        return view('checkout.cancel');
    }

    /**
     * @return array{success: string, cancel: string}
     */
    private function premiumStripeReturnUrls(): array
    {
        return [
            'success' => url()->route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel' => url()->route('checkout.cancel'),
        ];
    }
}
