<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final readonly class AuthService
{
    private const TOKEN_NAME = 'auth-token';
    private const DEFAULT_USER_ACTIVE = true;

    public function register(string $name, string $email, string $password): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => self::DEFAULT_USER_ACTIVE,
        ]);
    }

    public function createToken(User $user): string
    {
        return $user->createToken(self::TOKEN_NAME)->plainTextToken;
    }

    public function attemptLogin(string $email, string $password): bool
    {
        return Auth::attempt(['email' => $email, 'password' => $password]);
    }

    public function currentUser(): ?User
    {
        return Auth::user();
    }

    public function logout(): void
    {
        Auth::user()?->currentAccessToken()?->delete();
    }
}
