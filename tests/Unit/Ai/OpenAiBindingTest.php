<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Exceptions\MissingOpenAiApiKeyException;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\Services\Ai\AiClientInterface;
use App\Services\Ai\FakeAiClient;
use App\Services\Ai\OpenAiClient;
use App\Services\Ticket\SuggestedReply\FakeSuggestedReplyGenerator;
use App\Services\Ticket\SuggestedReply\OpenAiSuggestedReplyGenerator;
use App\Services\Ticket\SuggestedReply\SuggestedReplyGeneratorInterface;
use Tests\TestCase;

/**
 * OpenAI binding and usage rules:
 * - OPENAI_FAKE=true → fake implementations, no API key required.
 * - OPENAI_FAKE=false → real implementations are bound; missing key fails when calling OpenAI.
 */
final class OpenAiBindingTest extends TestCase
{
    /**
     * Given
     * - OPENAI_FAKE is true and no API key is configured.
     * When
     * - AI client and suggested-reply generator are resolved.
     * Then
     * - Fake implementations are returned without error.
     */
    public function test_when_fake_is_enabled_then_bindings_do_not_require_api_key(): void
    {
        // Arrange
        config([
            'openai.fake' => true,
            'openai.api_key' => null,
        ]);

        // Act
        $aiClient = $this->app->make(AiClientInterface::class);
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);

        // Assert
        $this->assertInstanceOf(FakeAiClient::class, $aiClient);
        $this->assertInstanceOf(FakeSuggestedReplyGenerator::class, $generator);
    }

    /**
     * Given
     * - OPENAI_FAKE is false and an API key is configured.
     * When
     * - AI client and suggested-reply generator are resolved.
     * Then
     * - Real OpenAI implementations are returned.
     */
    public function test_when_fake_is_disabled_and_api_key_exists_then_real_bindings_are_used(): void
    {
        // Arrange
        config([
            'openai.fake' => false,
            'openai.api_key' => 'sk-test-key',
        ]);

        // Act
        $aiClient = $this->app->make(AiClientInterface::class);
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);

        // Assert
        $this->assertInstanceOf(OpenAiClient::class, $aiClient);
        $this->assertInstanceOf(OpenAiSuggestedReplyGenerator::class, $generator);
    }

    /**
     * Given
     * - OPENAI_FAKE is false and the API key is missing.
     * When
     * - The AI client is resolved from the container.
     * Then
     * - Binding still succeeds (key is checked when OpenAI is called).
     */
    public function test_when_fake_is_disabled_and_api_key_is_missing_then_real_client_still_resolves(): void
    {
        // Arrange
        config([
            'openai.fake' => false,
            'openai.api_key' => '',
        ]);

        // Act
        $aiClient = $this->app->make(AiClientInterface::class);

        // Assert
        $this->assertInstanceOf(OpenAiClient::class, $aiClient);
    }

    /**
     * Given
     * - A real OpenAI client with no API key configured.
     * When
     * - An OpenAI-backed method is called.
     * Then
     * - A missing API key error is thrown.
     */
    public function test_when_openai_client_is_used_without_api_key_then_it_fails(): void
    {
        // Arrange
        config([
            'openai.fake' => false,
            'openai.api_key' => '',
        ]);
        $aiClient = $this->app->make(AiClientInterface::class);

        // Assert
        $this->expectException(MissingOpenAiApiKeyException::class);

        // Act
        $aiClient->moderate('hello');
    }

    /**
     * Given
     * - A real suggested-reply generator with no API key configured.
     * When
     * - Generation is requested with passages (would call OpenAI).
     * Then
     * - A missing API key error is thrown.
     */
    public function test_when_openai_generator_is_used_without_api_key_then_it_fails(): void
    {
        // Arrange
        config([
            'openai.fake' => false,
            'openai.api_key' => null,
        ]);
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);

        // Assert
        $this->expectException(MissingOpenAiApiKeyException::class);

        // Act
        $generator->generate('How do I reset my password?', [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ]);
    }
}
