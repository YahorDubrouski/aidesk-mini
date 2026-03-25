<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Auth;

use App\Http\Requests\BaseRequest;
use App\Rules\SafeRelativeRedirectPath;
use Illuminate\Validation\Rules\Password;

final class SessionRegisterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'redirect' => ['nullable', 'string', 'max:512', new SafeRelativeRedirectPath],
        ];
    }
}
