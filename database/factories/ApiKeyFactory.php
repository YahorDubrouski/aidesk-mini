<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $randomMaterial = bin2hex(random_bytes(32));
        $hash = hash('sha256', $randomMaterial);

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'key_hash' => $hash,
            'daily_quota' => 100,
            'daily_usage' => 0,
            'last_used_at' => null,
        ];
    }

    public function exhausted(): self
    {
        return $this->state(fn () => [
            'daily_usage' => 100,
        ]);
    }
}
