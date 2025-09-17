<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class OpenAiClient implements AiClientInterface
{
    public function analyzeTicket(string $body, ?string $subject = null): array
    {
        $model = config('openai.model', AiModel::Gpt4oMini->value);

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a support triage assistant. Reply in compact JSON.'],
                ['role' => 'user', 'content' => trim(($subject ? "Subject: $subject\n" : '') . "Body: $body")],
            ],
            'response_format' => ['type' => 'json_object'],
        ];

        $response = $this->buildClient()
            ->post('/chat/completions', $payload)
            ->json();

        $json = $response['choices'][0]['message']['content'] ?? '{}';
        $data = json_decode($json, true) ?: [];

        $usage = $response['usage'] ?? [];
        return [
            'provider' => AiProvider::OpenAI->value,
            'model' => $model,
            'result' => [
                'category' => $data['category'] ?? null,
                'urgency' => $data['urgency'] ?? 'normal',
                'sentiment' => $data['sentiment'] ?? null,
                'summary' => $data['summary'] ?? null,
                'reply_suggestion' => $data['reply_suggestion'] ?? null,
            ],
            'usage' => [
                'prompt_tokens' => (int)($usage['prompt_tokens'] ?? 0),
                'completion_tokens' => (int)($usage['completion_tokens'] ?? 0),
                'total_tokens' => (int)($usage['total_tokens'] ?? 0),
                'cost_usd' => number_format(0, 4, '.', ''),
            ],
        ];
    }

    private function buildClient(): PendingRequest
    {
        $baseUrl = rtrim((string)config('openai.base_url'), '/');

        return Http::withToken((string)config('openai.api_key'))
            ->timeout((int)config('openai.timeout', 15))
            ->retry(
                (int)config('openai.retry_times', 2),
                (int)config('openai.retry_sleep_ms', 250)
            )->baseUrl($baseUrl);
    }

    public function embedText(string $text): array
    {
        $model = (string)config('openai.embedding_model', 'text-embedding-3-small');

        $response = $this->buildClient()
            ->post('/embeddings', [
                'model' => $model,
                'input' => $text,
            ])
            ->json();

        $vector = $response['data'][0]['embedding'] ?? [];
        $usageTokens = (int)($response['usage']['total_tokens'] ?? 0);

        return [
            'model' => $model,
            'vector' => array_map('floatval', $vector),
            'usage' => ['tokens' => $usageTokens],
        ];
    }

    public function moderate(string $text): array
    {
        // Use text-moderation-latest

        $response = $this->buildClient()
            ->post('/moderations', [
                'model' => 'omni-moderation-latest',
                'input' => $text,
            ])
            ->json();

        $result = $response['results'][0] ?? [];
        return [
            'flagged' => (bool)($result['flagged'] ?? false),
            'category' => $result['categories'] ?? null,
            'reason' => null,
        ];
    }
}
