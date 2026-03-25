<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Auth;

use App\Http\Requests\BaseRequest;
use App\Rules\SafeRelativeRedirectPath;

final class SessionLoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
            'redirect' => ['nullable', 'string', 'max:512', new SafeRelativeRedirectPath],
        ];
    }
}
