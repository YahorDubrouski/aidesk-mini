<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\ApiKey\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_api_key(): void
    {
        $user = $this->createAuthenticatedUser();

        $response = $this->postJson('/api/api-keys', [
            'name' => 'Test API Key',
        ], $this->withAuth($user));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'key',
                    'daily_quota',
                    'daily_usage',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('api_keys', [
            'user_id' => $user->id,
            'name' => 'Test API Key',
        ]);
    }

    public function test_user_can_list_api_keys(): void
    {
        $user = $this->createAuthenticatedUser();

        ApiKey::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/api-keys', $this->withAuth($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'daily_quota',
                        'daily_usage',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_revoke_api_key(): void
    {
        $user = $this->createAuthenticatedUser();

        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/api-keys/{$apiKey->id}", [], $this->withAuth($user));

        $response->assertStatus(200)
            ->assertJson(['message' => 'API key revoked successfully']);

        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }

    public function test_user_cannot_revoke_other_user_api_key(): void
    {
        $user = $this->createAuthenticatedUser();
        $otherUser = User::factory()->create();

        $apiKey = ApiKey::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/api-keys/{$apiKey->id}", [], $this->withAuth($user));

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);

        $this->assertDatabaseHas('api_keys', ['id' => $apiKey->id]);
    }

    public function test_api_key_authentication_works(): void
    {
        $user = $this->createAuthenticatedUser();
        $apiKeyService = app(ApiKeyService::class);
        $result = $apiKeyService->generate($user, 'Test Key');
        $plainKey = $result['plainKey'];

        $response = $this->getJson('/api/tickets/test', [
            'X-API-Key' => $plainKey,
        ]);

        $response->assertStatus(404);
    }

    public function test_api_key_quota_tracking(): void
    {
        $user = $this->createAuthenticatedUser();
        $apiKeyService = app(ApiKeyService::class);
        $apiKeyService->generate($user, 'Test Key');

        $apiKey = ApiKey::query()
            ->where('user_id', $user->id)
            ->where('name', 'Test Key')
            ->first();

        $this->assertEquals(0, $apiKey->daily_usage);

        $apiKeyService->trackUsage($apiKey);
        $apiKey->refresh();

        $this->assertEquals(1, $apiKey->daily_usage);
    }
}
