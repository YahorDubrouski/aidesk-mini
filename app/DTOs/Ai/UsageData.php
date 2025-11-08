<?php

declare(strict_types=1);

namespace App\DTOs\Ai;

final readonly class UsageData
{
    public function __construct(
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
        public string $costUsd,
    ) {}

    public function toArray(): array
    {
        return [
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'cost_usd' => $this->costUsd,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: (int) ($data['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['completion_tokens'] ?? 0),
            totalTokens: (int) ($data['total_tokens'] ?? 0),
            costUsd: $data['cost_usd'] ?? '0.0000',
        );
    }
}
