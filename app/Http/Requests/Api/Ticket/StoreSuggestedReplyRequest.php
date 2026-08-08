<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Ticket;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSuggestedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }
}
