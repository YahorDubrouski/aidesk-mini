<?php

declare(strict_types=1);

namespace App\Services\Ticket\SuggestedReply;

use App\DTOs\Ai\UsageData;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplySource;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;

/**
 * Turns model JSON into a SuggestedReplyResult and drops invented article ids.
 */
final class SuggestedReplyResponseParser
{
    /**
     * @param  list<SuggestedReplyPassage>  $passages
     */
    public function parse(
        mixed $decodedPayload,
        array $passages,
        AiModel $model,
        UsageData $usage,
    ): SuggestedReplyResult {
        if (! is_array($decodedPayload)) {
            return SuggestedReplyResult::refused(
                refuseReason: SuggestedReplyResult::REFUSE_REASON_INVALID_MODEL_RESPONSE,
                provider: AiProvider::OpenAI,
                model: $model,
                usage: $usage,
            );
        }

        $passagesById = [];
        foreach ($passages as $passage) {
            $passagesById[$passage->id] = $passage;
        }

        if (($decodedPayload['refused'] ?? false) === true) {
            $reason = (string) ($decodedPayload['refuse_reason']
                ?? SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT);

            return SuggestedReplyResult::refused(
                refuseReason: $reason !== '' ? $reason : SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT,
                provider: AiProvider::OpenAI,
                model: $model,
                usage: $usage,
            );
        }

        $answer = trim((string) ($decodedPayload['answer'] ?? ''));
        $sources = $this->filterSources($decodedPayload['sources'] ?? [], $passagesById);

        if ($answer === '' || $sources === []) {
            return SuggestedReplyResult::refused(
                refuseReason: SuggestedReplyResult::REFUSE_REASON_INSUFFICIENT_CONTEXT,
                provider: AiProvider::OpenAI,
                model: $model,
                usage: $usage,
            );
        }

        return new SuggestedReplyResult(
            answer: $answer,
            sources: $sources,
            refused: false,
            refuseReason: null,
            provider: AiProvider::OpenAI,
            model: $model,
            usage: $usage,
        );
    }

    /**
     * Keep only source ids that exist in the retrieved passages.
     * Example: model cites id 99 but passages were [12, 15] → drop 99.
     *
     * @param  array<int, SuggestedReplyPassage>  $passagesById
     * @return list<SuggestedReplySource>
     */
    private function filterSources(mixed $rawSources, array $passagesById): array
    {
        if (! is_array($rawSources)) {
            return [];
        }

        $sources = [];
        $seenIds = [];

        foreach ($rawSources as $rawSource) {
            if (! is_array($rawSource)) {
                continue;
            }

            $id = (int) ($rawSource['id'] ?? 0);
            if ($id <= 0 || ! isset($passagesById[$id]) || isset($seenIds[$id])) {
                continue;
            }

            $passage = $passagesById[$id];
            $seenIds[$id] = true;
            $sources[] = new SuggestedReplySource(
                id: $id,
                title: (string) ($rawSource['title'] ?? $passage->title),
                similarity: $passage->similarity,
            );
        }

        return $sources;
    }
}
