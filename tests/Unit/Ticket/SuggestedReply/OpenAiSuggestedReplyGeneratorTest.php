<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Services\Ai\OpenAiClient;
use App\Services\Ticket\SuggestedReply\OpenAiSuggestedReplyGenerator;
use App\Services\Ticket\SuggestedReply\SuggestedReplyResponseParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * OpenAI suggested-reply generator:
 * - Empty passages refuse without HTTP.
 * - Successful chat JSON becomes a grounded result.
 * - Invented source ids from the model are filtered out.
 */
final class OpenAiSuggestedReplyGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'openai.fake' => false,
            'openai.api_key' => 'sk-test-key',
            'openai.base_url' => 'https://api.openai.com/v1',
            'openai.model' => 'gpt-4o-mini',
            'openai.retry_times' => 2,
            'openai.retry_sleep_ms' => 1,
        ]);
    }

    /**
     * Given
     * - No passages are provided.
     * When
     * - Generation is requested.
     * Then
     * - The reply is refused as empty_passages and OpenAI is not called.
     */
    public function test_when_passages_are_empty_then_openai_is_not_called(): void
    {
        // Arrange
        Http::fake();
        $generator = $this->makeGenerator();

        // Act
        $result = $generator->generate('How do I reset my password?', []);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES, $result->refuseReason);
        Http::assertNothingSent();
    }

    /**
     * Given
     * - OpenAI returns a grounded JSON answer citing a known passage id.
     * When
     * - Generation is requested with that passage.
     * Then
     * - The answer and source are returned.
     */
    public function test_when_openai_returns_grounded_json_then_result_contains_sources(): void
    {
        // Arrange
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'answer' => 'Open Settings → Security → Reset password.',
                                'sources' => [['id' => 12, 'title' => 'Password reset']],
                                'refused' => false,
                                'refuse_reason' => null,
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 40,
                    'total_tokens' => 140,
                ],
            ]),
        ]);
        $generator = $this->makeGenerator();

        // Act
        $result = $generator->generate('How do I reset my password?', [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
                similarity: 0.88,
            ),
        ]);

        // Assert
        $this->assertFalse($result->refused);
        $this->assertSame('Open Settings → Security → Reset password.', $result->answer);
        $this->assertSame(12, $result->sources[0]->id);
        $this->assertSame(0.88, $result->sources[0]->similarity);
        $this->assertSame(140, $result->usage->totalTokens);
        Http::assertSentCount(1);
    }

    /**
     * Given
     * - OpenAI returns a source id that is not in the provided passages.
     * When
     * - Generation is requested.
     * Then
     * - The reply is refused after filtering invented sources.
     */
    public function test_when_openai_invents_source_id_then_reply_is_refused(): void
    {
        // Arrange
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'answer' => 'Something invented',
                                'sources' => [['id' => 999, 'title' => 'Nope']],
                                'refused' => false,
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ],
            ]),
        ]);
        $generator = $this->makeGenerator();

        // Act
        $result = $generator->generate('How do I reset my password?', [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ]);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $result->refuseReason);
        $this->assertSame([], $result->sources);
    }

    /**
     * Given
     * - OpenAI returns malformed message content.
     * When
     * - Generation is requested.
     * Then
     * - The reply is refused as invalid_model_response.
     */
    public function test_when_openai_returns_malformed_json_then_reply_is_refused(): void
    {
        // Arrange
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'not-json',
                        ],
                    ],
                ],
                'usage' => [],
            ]),
        ]);
        $generator = $this->makeGenerator();

        // Act
        $result = $generator->generate('How do I reset my password?', [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ]);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INVALID_MODEL_RESPONSE, $result->refuseReason);
    }

    /**
     * Given
     * - A passage is available for a password-reset question.
     * When
     * - Generation is requested.
     * Then
     * - The OpenAI request includes a structured RAG system contract and delimited user context.
     */
    public function test_when_generation_runs_then_request_uses_structured_rag_prompts(): void
    {
        // Arrange
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'answer' => 'Open Settings → Security → Reset password.',
                                'sources' => [['id' => 12, 'title' => 'Password reset']],
                                'refused' => false,
                                'refuse_reason' => null,
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 100,
                    'completion_tokens' => 40,
                    'total_tokens' => 140,
                ],
            ]),
        ]);
        $generator = $this->makeGenerator();

        // Act
        $generator->generate('How do I reset my password?', [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ]);

        // Assert
        Http::assertSent(function ($request): bool {
            $payload = $request->data();
            $system = $payload['messages'][0]['content'] ?? '';
            $user = $payload['messages'][1]['content'] ?? '';

            return str_contains($system, 'retrieval-augmented generation')
                && str_contains($system, 'Grounding rules')
                && str_contains($system, 'Output contract')
                && str_contains($user, '<customer_question>')
                && str_contains($user, '<passage id="12"')
                && str_contains($user, '</retrieved_passages>');
        });
    }

    private function makeGenerator(): OpenAiSuggestedReplyGenerator
    {
        return new OpenAiSuggestedReplyGenerator(
            new OpenAiClient,
            new SuggestedReplyResponseParser,
        );
    }
}
