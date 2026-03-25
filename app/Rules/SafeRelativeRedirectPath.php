<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Allows only same-site relative paths (no open redirects).
 */
final class SafeRelativeRedirectPath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (! $this->isSafeRelativePath($value)) {
            $fail('The :attribute must be a safe relative path.');
        }
    }

    private function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return false;
        }
        if (str_contains($path, "\0") || str_contains($path, '\\')) {
            return false;
        }
        if (preg_match('/[\s@]/', $path)) {
            return false;
        }

        return true;
    }
}
