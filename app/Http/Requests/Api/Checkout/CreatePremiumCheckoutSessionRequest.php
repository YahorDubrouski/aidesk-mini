<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Checkout;

use App\Http\Requests\BaseRequest;

/**
 * POST /api/checkout/premium — optional query flag to redirect instead of JSON.
 */
final class CreatePremiumCheckoutSessionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'redirect' => ['sometimes', 'boolean'],
        ];
    }
}
