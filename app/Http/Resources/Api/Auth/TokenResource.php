<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TokenResource extends JsonResource
{
    public function __construct(
        private readonly User $user,
        private readonly string $token
    ) {
        parent::__construct($user);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'token' => $this->token,
        ];
    }
}
