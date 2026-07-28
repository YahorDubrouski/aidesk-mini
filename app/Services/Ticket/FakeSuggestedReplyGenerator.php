<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Ai\UsageData;
use App\DTOs\Ticket\SuggestedReplyResult;
use App\DTOs\Ticket\SuggestedReplySource;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;

/**
 * Deterministic grounded reply for local/CI use. Does not call OpenAI or require an API key.
 */
final class FakeSuggestedReplyGenerator implements SuggestedReplyGeneratorInterface
{
    /**
     * Put this token in the question during tests to force a refuse without empty passages.
     * Example: question "__NO_MATCH__ how do I pay?" → refused / insufficient_context.
     */
    public const NO_MATCH_SENTINEL = '__NO_MATCH__';

    private const STOP_WORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
        'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that',
        'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'what', 'which', 'who',
        'where', 'when', 'why', 'how', 'my', 'me', 'our', 'your',
    ];

    public function generate(string $question, array $passages): SuggestedReplyResult
    {
        if ($passages === []) {
            return $this->refuse(SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES);
        }

        if (trim($question) === '' || str_contains($question, self::NO_MATCH_SENTINEL)) {
            return $this->refuse(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT);
        }

        $matchedPassages = $this->matchPassagesByWords($question, $passages);

        if ($matchedPassages === []) {
            return $this->refuse(SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT);
        }

        $best = $matchedPassages[0];
        $snippet = mb_substr(trim((string) $best['body']), 0, 200);
        $answer = sprintf('Based on "%s": %s', $best['title'], $snippet);

        $sources = array_map(
            static fn (array $passage): SuggestedReplySource => new SuggestedReplySource(
                id: (int) $passage['id'],
                title: (string) $passage['title'],
                similarity: isset($passage['similarity']) ? (float) $passage['similarity'] : null,
            ),
            $matchedPassages,
        );

        return new SuggestedReplyResult(
            answer: $answer,
            sources: $sources,
            refused: false,
            refuseReason: null,
            provider: AiProvider::OpenAI,
            model: $this->configuredModel(),
            usage: new UsageData(
                promptTokens: 50,
                completionTokens: 40,
                totalTokens: 90,
                costUsd: '0.0000',
            ),
        );
    }

    /**
     * Keep passages that share meaningful words with the question, best score first.
     * Example: question "reset password" + passages [Password reset, Billing FAQ]
     * → only "Password reset" (words reset + password hit).
     *
     * @param  list<array{id: int, title: string, body: string, similarity?: float|null}>  $passages
     * @return list<array{id: int, title: string, body: string, similarity?: float|null}>
     */
    private function matchPassagesByWords(string $question, array $passages): array
    {
        $questionWords = $this->extractWords($question);

        if ($questionWords === []) {
            return [];
        }

        $scored = [];
        foreach ($passages as $passage) {
            $haystack = strtolower(trim($passage['title'].' '.$passage['body']));
            $score = 0;
            foreach ($questionWords as $word) {
                if (str_contains($haystack, $word)) {
                    $score++;
                }
            }

            if ($score > 0) {
                $scored[] = ['score' => $score, 'passage' => $passage];
            }
        }

        usort(
            $scored,
            static fn (array $left, array $right): int => $right['score'] <=> $left['score'],
        );

        return array_map(static fn (array $item): array => $item['passage'], $scored);
    }

    /**
     * @return list<string>
     */
    private function extractWords(string $text): array
    {
        $normalized = strtolower($text);
        // Keep letters/digits only so "password?" becomes "password".
        $normalized = preg_replace('/[^a-z0-9\s]+/u', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/', trim($normalized)) ?: [];

        $words = [];
        foreach ($parts as $part) {
            if (strlen($part) <= 2 || in_array($part, self::STOP_WORDS, true)) {
                continue;
            }

            $words[] = $part;
        }

        return array_values(array_unique($words));
    }

    private function refuse(string $reason): SuggestedReplyResult
    {
        return SuggestedReplyResult::refused(
            refuseReason: $reason,
            provider: AiProvider::OpenAI,
            model: $this->configuredModel(),
            usage: new UsageData(0, 0, 0, '0.0000'),
        );
    }

    private function configuredModel(): AiModel
    {
        $modelValue = (string) config('openai.model', AiModel::Gpt4oMini->value);

        return AiModel::tryFrom($modelValue) ?? AiModel::Gpt4oMini;
    }
}
