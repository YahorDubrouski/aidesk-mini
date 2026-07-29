<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\DTOs\Ai\UsageData;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplySource;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ticket suggested-reply DTO contract:
 * - Happy path: answer + sources round-trip through arrays.
 * - Refuse path: refused flag, reason, empty sources, fixed default answer helper.
 */
final class SuggestedReplyResultTest extends TestCase
{
    /**
     * Given
     * - A grounded reply with one cited article and usage metadata.
     * When
     * - The result is serialized to an array and rebuilt from that array.
     * Then
     * - Answer, sources, and usage match the original values.
     */
    public function test_when_happy_path_result_is_serialized_then_round_trip_preserves_fields(): void
    {
        // Arrange
        $original = new SuggestedReplyResult(
            answer: 'Open Settings → Security → Reset password.',
            sources: [
                new SuggestedReplySource(id: 12, title: 'Password reset', similarity: 0.87),
            ],
            refused: false,
            refuseReason: null,
            provider: AiProvider::OpenAI,
            model: AiModel::Gpt4oMini,
            usage: new UsageData(
                promptTokens: 120,
                completionTokens: 40,
                totalTokens: 160,
                costUsd: '0.0000',
            ),
        );

        // Act
        $restored = SuggestedReplyResult::fromArray($original->toArray());

        // Assert
        $this->assertSame($original->answer, $restored->answer);
        $this->assertFalse($restored->refused);
        $this->assertNull($restored->refuseReason);
        $this->assertCount(1, $restored->sources);
        $this->assertSame(12, $restored->sources[0]->id);
        $this->assertSame('Password reset', $restored->sources[0]->title);
        $this->assertSame(0.87, $restored->sources[0]->similarity);
        $this->assertSame(AiProvider::OpenAI, $restored->provider);
        $this->assertSame(AiModel::Gpt4oMini, $restored->model);
        $this->assertSame(160, $restored->usage->totalTokens);
    }

    /**
     * Given
     * - A refuse payload with empty sources and a stable refuse reason.
     * When
     * - The result is serialized and rebuilt from that array.
     * Then
     * - Refuse metadata is preserved and sources stay empty.
     */
    public function test_when_refuse_payload_is_serialized_then_round_trip_preserves_refuse_shape(): void
    {
        // Arrange
        $original = SuggestedReplyResult::refused(
            refuseReason: SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT,
            provider: AiProvider::OpenAI,
            model: AiModel::Gpt4oMini,
            usage: new UsageData(0, 0, 0, '0.0000'),
        );

        // Act
        $restored = SuggestedReplyResult::fromArray($original->toArray());

        // Assert
        $this->assertTrue($restored->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $restored->refuseReason);
        $this->assertSame(SuggestedReplyResult::DEFAULT_REFUSE_ANSWER, $restored->answer);
        $this->assertSame([], $restored->sources);
    }

    /**
     * Given
     * - An array with a non-array entry mixed into sources.
     * When
     * - The result is built via fromArray.
     * Then
     * - Invalid source entries are skipped and valid ones are kept.
     */
    public function test_when_sources_contain_invalid_entries_then_they_are_skipped(): void
    {
        // Arrange
        $payload = [
            'answer' => 'Use the billing portal.',
            'sources' => [
                'not-an-array',
                ['id' => 5, 'title' => 'Billing FAQ'],
            ],
            'refused' => false,
            'refuse_reason' => null,
            'provider' => AiProvider::OpenAI->value,
            'model' => AiModel::Gpt4oMini->value,
            'usage' => [],
        ];

        // Act
        $result = SuggestedReplyResult::fromArray($payload);

        // Assert
        $this->assertCount(1, $result->sources, 'Only valid source objects should be kept');
        $this->assertSame(5, $result->sources[0]->id);
    }
}
