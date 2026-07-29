<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\DTOs\Ai\UsageData;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Enums\Ai\AiModel;
use App\Services\Ticket\SuggestedReply\SuggestedReplyResponseParser;
use PHPUnit\Framework\TestCase;

/**
 * OpenAI suggested-reply JSON parsing:
 * - Happy path keeps only source ids present in passages.
 * - Invented ids are dropped; empty sources after filtering refuse.
 * - Malformed payloads refuse as invalid_model_response.
 */
final class SuggestedReplyResponseParserTest extends TestCase
{
    /**
     * Given
     * - Model JSON answers with a source id that exists in passages.
     * When
     * - The response is parsed.
     * Then
     * - Answer and that source are kept (similarity taken from the passage).
     */
    public function test_when_model_cites_known_passage_then_source_is_kept(): void
    {
        // Arrange
        $parser = new SuggestedReplyResponseParser;
        $passages = [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Reset via Settings.',
                similarity: 0.9,
            ),
        ];

        // Act
        $result = $parser->parse(
            [
                'answer' => 'Reset via Settings.',
                'sources' => [['id' => 12, 'title' => 'Password reset']],
                'refused' => false,
                'refuse_reason' => null,
            ],
            $passages,
            AiModel::Gpt4oMini,
            new UsageData(10, 5, 15, '0.0000'),
        );

        // Assert
        $this->assertFalse($result->refused);
        $this->assertSame('Reset via Settings.', $result->answer);
        $this->assertCount(1, $result->sources);
        $this->assertSame(12, $result->sources[0]->id);
        $this->assertSame(0.9, $result->sources[0]->similarity);
    }

    /**
     * Given
     * - Model JSON cites an article id that was not in the retrieved passages.
     * When
     * - The response is parsed.
     * Then
     * - The invented source is dropped and the reply is refused.
     */
    public function test_when_model_cites_unknown_id_then_reply_is_refused(): void
    {
        // Arrange
        $parser = new SuggestedReplyResponseParser;
        $passages = [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Reset via Settings.',
            ),
        ];

        // Act
        $result = $parser->parse(
            [
                'answer' => 'Invented answer',
                'sources' => [['id' => 99, 'title' => 'Hallucinated']],
                'refused' => false,
            ],
            $passages,
            AiModel::Gpt4oMini,
            new UsageData(0, 0, 0, '0.0000'),
        );

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $result->refuseReason);
        $this->assertSame([], $result->sources);
    }

    /**
     * Given
     * - Model content is not valid JSON object data.
     * When
     * - The response is parsed.
     * Then
     * - The reply is refused as an invalid model response.
     */
    public function test_when_payload_is_not_an_array_then_reply_is_refused_as_invalid(): void
    {
        // Arrange
        $parser = new SuggestedReplyResponseParser;

        // Act
        $result = $parser->parse(
            null,
            [
                new SuggestedReplyPassage(
                    id: 12,
                    title: 'Password reset',
                    body: 'Reset via Settings.',
                ),
            ],
            AiModel::Gpt4oMini,
            new UsageData(0, 0, 0, '0.0000'),
        );

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INVALID_MODEL_RESPONSE, $result->refuseReason);
    }

    /**
     * Given
     * - Model JSON sets refused=true.
     * When
     * - The response is parsed.
     * Then
     * - Sources are empty and the refuse reason is preserved.
     */
    public function test_when_model_refuses_then_sources_are_empty(): void
    {
        // Arrange
        $parser = new SuggestedReplyResponseParser;

        // Act
        $result = $parser->parse(
            [
                'answer' => '',
                'sources' => [['id' => 12, 'title' => 'Password reset']],
                'refused' => true,
                'refuse_reason' => SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT,
            ],
            [
                new SuggestedReplyPassage(
                    id: 12,
                    title: 'Password reset',
                    body: 'Reset via Settings.',
                ),
            ],
            AiModel::Gpt4oMini,
            new UsageData(1, 1, 2, '0.0000'),
        );

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame([], $result->sources);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $result->refuseReason);
    }
}
