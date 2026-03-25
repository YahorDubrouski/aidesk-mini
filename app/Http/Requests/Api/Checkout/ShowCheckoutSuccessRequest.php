<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Checkout;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * GET /checkout/success — Stripe appends ?session_id=cs_...
 */
final class ShowCheckoutSuccessRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'session_id' => ['nullable', 'string', 'max:128',],
        ];
    }

    /**
     * Web UX: invalid session_id should not show a raw 422 page.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('checkout.upgrade')->withErrors($validator)
        );
    }
}
