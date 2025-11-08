<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createAuthenticatedUser(): User
    {
        return User::factory()->create();
    }

    protected function getAuthToken(User $user): string
    {
        return $user->createToken('auth-token')->plainTextToken;
    }

    protected function withAuth(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$this->getAuthToken($user),
        ];
    }
}
