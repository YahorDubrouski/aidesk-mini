<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\Auth\TokenResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->authService->register(
            $validated['name'],
            $validated['email'],
            $validated['password']
        );

        $token = $this->authService->createToken($user);

        return (new TokenResource($user, $token))->response()->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! $this->authService->attemptLogin($validated['email'], $validated['password'])) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $this->authService->currentUser();
        $token = $this->authService->createToken($user);

        return (new TokenResource($user, $token))->response();
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
