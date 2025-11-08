<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\ApiKey;

use App\Http\Requests\BaseRequest;

final class StoreApiKeyRequest extends BaseRequest
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
