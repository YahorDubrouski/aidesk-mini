<?php

declare(strict_types=1);

namespace App\Services\ApiKey;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class ApiKeyService
{
    private const DEFAULT_DAILY_QUOTA = 100;

    public function generate(User $user, string $name): array
    {
        $plainKey = $this->generateKey();
        $keyHash = Hash::make($plainKey);

        $apiKey = ApiKey::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'key_hash' => $keyHash,
            'daily_quota' => self::DEFAULT_DAILY_QUOTA,
            'daily_usage' => 0,
        ]);

        return ['apiKey' => $apiKey, 'plainKey' => $plainKey];
    }

    public function validate(string $key): ?ApiKey
    {
        $apiKeys = ApiKey::query()
            ->whereNotNull('key_hash')
            ->get();

        $apiKey = $apiKeys->first(fn (ApiKey $apiKey) => Hash::check($key, $apiKey->key_hash));

        if (!$apiKey) {
            return null;
        }

        $this->resetDailyUsageIfNeeded($apiKey);

        if ($this->isQuotaExceeded($apiKey)) {
            return null;
        }

        return $apiKey;
    }

    public function trackUsage(ApiKey $apiKey): void
    {
        $apiKey->increment('daily_usage');
        $apiKey->update(['last_used_at' => now()]);
    }

    public function revoke(ApiKey $apiKey): void
    {
        $apiKey->delete();
    }

    private function generateKey(): string
    {
        return 'ak_' . Str::random(40);
    }

    private function resetDailyUsageIfNeeded(ApiKey $apiKey): void
    {
        if (!$apiKey->last_used_at || $apiKey->last_used_at->isToday()) {
            return;
        }

        $apiKey->update([
            'daily_usage' => 0,
        ]);
    }

    private function isQuotaExceeded(ApiKey $apiKey): bool
    {
        return $apiKey->daily_usage >= $apiKey->daily_quota;
    }
}
