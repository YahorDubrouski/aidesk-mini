<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Ai\UsageData;
use App\DTOs\Ticket\SuggestedReplyResult;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use App\Exceptions\MissingOpenAiApiKeyException;
use RuntimeException;

/**
 * Live OpenAI grounded reply. Bound when OPENAI_FAKE is false.
 */
final class OpenAiSuggestedReplyGenerator implements SuggestedReplyGeneratorInterface
{
    public function generate(string $question, array $passages): SuggestedReplyResult
    {
        if ($passages === []) {
            return SuggestedReplyResult::refused(
                refuseReason: SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES,
                provider: AiProvider::OpenAI,
                model: $this->configuredModel(),
                usage: new UsageData(0, 0, 0, '0.0000'),
            );
        }

        $this->ensureApiKeyConfigured();

        // Wired in the next iteration (live chat completion + JSON parse).
        throw new RuntimeException('OpenAI grounded reply is not implemented yet.');
    }

    private function ensureApiKeyConfigured(): void
    {
        if (trim((string) config('openai.api_key')) === '') {
            throw new MissingOpenAiApiKeyException;
        }
    }

    private function configuredModel(): AiModel
    {
        $modelValue = (string) config('openai.model', AiModel::Gpt4oMini->value);

        return AiModel::tryFrom($modelValue) ?? AiModel::Gpt4oMini;
    }
}
