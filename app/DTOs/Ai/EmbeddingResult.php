<?php

declare(strict_types=1);

namespace App\DTOs\Ai;

final readonly class EmbeddingResult
{
    public function __construct(
        public string $model,
        /** @var float[] */
        public array $vector,
        public int $tokens,
    ) {}

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'vector' => $this->vector,
            'usage' => ['tokens' => $this->tokens],
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'] ?? '',
            vector: array_map('floatval', $data['vector'] ?? []),
            tokens: (int) ($data['usage']['tokens'] ?? $data['tokens'] ?? 0),
        );
    }
}
