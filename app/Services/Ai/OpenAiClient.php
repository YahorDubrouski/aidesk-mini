<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\Ai\EmbeddingResult;
use App\DTOs\Ai\ModerationResult;
use App\DTOs\Ai\TextAnalysisResult;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class OpenAiClient implements AiClientInterface
{
    /**
     * @throws ConnectionException
     */
    public function analyzeText(string $body, ?string $subject = null): TextAnalysisResult
    {
        $modelValue = config('openai.model', AiModel::Gpt4oMini->value);

        $payload = [
            'model' => $modelValue,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a support triage assistant. Reply in compact JSON.'],
                ['role' => 'user', 'content' => trim(($subject ? "Subject: $subject\n" : '')."Body: $body")],
            ],
            'response_format' => ['type' => 'json_object'],
        ];

        $response = $this->buildClient()
            ->post('/chat/completions', $payload)
            ->json();

        $json = $response['choices'][0]['message']['content'] ?? '{}';
        $data = json_decode($json, true) ?: [];
        $usage = $response['usage'] ?? [];

        return TextAnalysisResult::fromArray([
            'provider' => AiProvider::OpenAI->value,
            'model' => $modelValue,
            'result' => $data,
            'usage' => $usage,
        ]);
    }

    /**
     * Build HTTP client with retry logic (min 2 retries for unstable connections).
     */
    private function buildClient(): PendingRequest
    {
        $baseUrl = rtrim((string) config('openai.base_url'), '/');

        return Http::withToken((string) config('openai.api_key'))
            ->timeout((int) config('openai.timeout', 15))
            ->retry(
                $this->getRetryTimes(),
                $this->getRetrySleepMs()
            )->baseUrl($baseUrl);
    }

    /**
     * Get retry attempts (minimum 2 for unstable connections).
     */
    private function getRetryTimes(): int
    {
        $configuredRetries = (int) config('openai.retry_times', 2);

        return max(2, $configuredRetries);
    }

    private function getRetrySleepMs(): int
    {
        return (int) config('openai.retry_sleep_ms', 250);
    }

    /**
     * @throws ConnectionException
     */
    public function embedText(string $text): EmbeddingResult
    {
        $model = (string) config('openai.embedding_model', 'text-embedding-3-small');

        $response = $this->buildClient()
            ->post('/embeddings', [
                'model' => $model,
                'input' => $text,
            ])
            ->json();

        $vector = $response['data'][0]['embedding'] ?? [];
        $usageTokenCount = (int) ($response['usage']['total_tokens'] ?? 0);

        return EmbeddingResult::fromArray([
            'model' => $model,
            'vector' => $vector,
            'usage' => ['tokens' => $usageTokenCount],
        ]);
    }

    /**
     * @throws ConnectionException
     */
    public function moderate(string $text): ModerationResult
    {
        $response = $this->buildClient()
            ->post('/moderations', [
                'model' => 'omni-moderation-latest',
                'input' => $text,
            ])
            ->json();

        $result = $response['results'][0] ?? [];

        return ModerationResult::fromArray([
            'flagged' => $result['flagged'] ?? false,
            'category' => $result['categories'] ?? null,
            'reason' => null,
        ]);
    }
}
