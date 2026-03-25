<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Auth;

use App\Http\Requests\BaseRequest;
use App\Rules\SafeRelativeRedirectPath;

/**
 * Validates optional ?redirect= on login/register GET routes.
 */
final class SessionAuthRedirectQueryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'redirect' => ['nullable', 'string', 'max:512', new SafeRelativeRedirectPath],
        ];
    }
}
