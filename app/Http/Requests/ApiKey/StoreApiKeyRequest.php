<?php

declare(strict_types=1);

namespace App\Http\Requests\ApiKey;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
