<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\ApiKey;

use App\Http\Resources\BaseShowResource;
use App\Models\ApiKey;
use Illuminate\Http\Request;

final class ShowResource extends BaseShowResource
{
    public function __construct(
        ApiKey $apiKey,
        private readonly ?string $plainKey = null
    ) {
        parent::__construct($apiKey);
    }

    public function toArray(Request $request): array
    {
        /** @var ApiKey $apiKey */
        $apiKey = $this->resource;

        $data = [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'daily_quota' => $apiKey->daily_quota,
            'daily_usage' => $apiKey->daily_usage,
            'last_used_at' => $apiKey->last_used_at?->toIso8601String(),
            'created_at' => $apiKey->created_at->toIso8601String(),
        ];

        if ($this->plainKey !== null) {
            $data['key'] = $this->plainKey;
        }

        return $data;
    }
}
