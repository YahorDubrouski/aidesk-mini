<?php

declare(strict_types=1);

namespace Tests\Unit\Ticket\SuggestedReply;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Services\Ticket\SuggestedReply\FakeSuggestedReplyGenerator;
use App\Services\Ticket\SuggestedReply\SuggestedReplyGeneratorInterface;
use Tests\TestCase;

/**
 * Grounded suggested-reply generator (fake implementation):
 * - Empty passages refuse without inventing an answer.
 * - Word overlap selects relevant passages; irrelevant ones are dropped.
 * - No word overlap (or sentinel) forces insufficient-context refuse.
 */
final class SuggestedReplyGeneratorTest extends TestCase
{
    /**
     * Given
     * - OPENAI_FAKE is enabled for the test suite.
     * When
     * - The suggested-reply generator is resolved from the container.
     * Then
     * - The fake implementation is bound.
     */
    public function test_when_openai_fake_is_enabled_then_container_binds_fake_generator(): void
    {
        // Act
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);

        // Assert
        $this->assertInstanceOf(FakeSuggestedReplyGenerator::class, $generator);
    }

    /**
     * Given
     * - No knowledge passages are provided.
     * When
     * - A grounded reply is generated for a normal question.
     * Then
     * - The result is refused with empty_passages and no sources.
     */
    public function test_when_passages_are_empty_then_reply_is_refused(): void
    {
        // Arrange
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);

        // Act
        $result = $generator->generate('How do I reset my password?', []);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES, $result->refuseReason);
        $this->assertSame(SuggestedReplyResult::DEFAULT_REFUSE_ANSWER, $result->answer);
        $this->assertSame([], $result->sources);
    }

    /**
     * Given
     * - A relevant password article and an unrelated billing article.
     * When
     * - A grounded reply is generated for a password-reset question.
     * Then
     * - Only the matching article is cited and used in the answer.
     */
    public function test_when_passages_exist_then_only_word_matched_sources_are_used(): void
    {
        // Arrange
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);
        $passages = [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password. Link expires in 24 hours.',
                similarity: 0.91,
            ),
            new SuggestedReplyPassage(
                id: 15,
                title: 'Billing FAQ',
                body: 'Invoices are emailed on the first of each month.',
                similarity: 0.72,
            ),
        ];

        // Act
        $result = $generator->generate('How do I reset my password?', $passages);

        // Assert
        $this->assertFalse($result->refused);
        $this->assertNull($result->refuseReason);
        $this->assertStringContainsString('Password reset', $result->answer);
        $this->assertCount(1, $result->sources);
        $this->assertSame(12, $result->sources[0]->id);
        $this->assertSame(0.91, $result->sources[0]->similarity);
    }

    /**
     * Given
     * - Two passages where only one shares words with the question, and another that shares fewer.
     * When
     * - A grounded reply is generated.
     * Then
     * - Sources are ordered by match strength and the answer uses the best match.
     */
    public function test_when_multiple_passages_match_then_best_score_leads_answer(): void
    {
        // Arrange
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);
        $passages = [
            new SuggestedReplyPassage(
                id: 20,
                title: 'Password tips',
                body: 'Never share your password with anyone.',
            ),
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ];

        // Act
        $result = $generator->generate('How do I reset my password?', $passages);

        // Assert
        $this->assertFalse($result->refused);
        $this->assertSame(12, $result->sources[0]->id);
        $this->assertStringContainsString('Password reset', $result->answer);
        $this->assertSame([12, 20], array_map(static fn ($source) => $source->id, $result->sources));
    }

    /**
     * Given
     * - Passages exist but none share meaningful words with the question.
     * When
     * - A grounded reply is generated.
     * Then
     * - The result is refused for insufficient context.
     */
    public function test_when_no_passage_shares_words_then_reply_is_refused(): void
    {
        // Arrange
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);
        $passages = [
            new SuggestedReplyPassage(
                id: 15,
                title: 'Billing FAQ',
                body: 'Invoices are emailed on the first of each month.',
            ),
        ];

        // Act
        $result = $generator->generate('How do I reset my password?', $passages);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $result->refuseReason);
        $this->assertSame([], $result->sources);
    }

    /**
     * Given
     * - Passages exist but the question contains the no-match test sentinel.
     * When
     * - A grounded reply is generated.
     * Then
     * - The result is refused for insufficient context with empty sources.
     */
    public function test_when_question_has_no_match_sentinel_then_reply_is_refused(): void
    {
        // Arrange
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);
        $passages = [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ];

        // Act
        $result = $generator->generate(
            FakeSuggestedReplyGenerator::NO_MATCH_SENTINEL.' how do I fly to the moon?',
            $passages,
        );

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $result->refuseReason);
        $this->assertSame([], $result->sources);
    }

    /**
     * Given
     * - Passages exist but the question is blank.
     * When
     * - A grounded reply is generated.
     * Then
     * - The result is refused for insufficient context.
     */
    public function test_when_question_is_blank_then_reply_is_refused(): void
    {
        // Arrange
        $generator = $this->app->make(SuggestedReplyGeneratorInterface::class);
        $passages = [
            new SuggestedReplyPassage(
                id: 12,
                title: 'Password reset',
                body: 'Open Settings → Security → Reset password.',
            ),
        ];

        // Act
        $result = $generator->generate('   ', $passages);

        // Assert
        $this->assertTrue($result->refused);
        $this->assertSame(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT, $result->refuseReason);
    }
}
