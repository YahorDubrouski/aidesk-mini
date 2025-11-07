<?php

declare(strict_types=1);

namespace App\DTOs\Ai;

final readonly class TextAnalysisData
{
    public function __construct(
        public ?string $category,
        public string $urgency,
        public ?string $sentiment,
        public ?string $summary,
        public ?string $replySuggestion,
    ) {
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'urgency' => $this->urgency,
            'sentiment' => $this->sentiment,
            'summary' => $this->summary,
            'reply_suggestion' => $this->replySuggestion,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            category: $data['category'] ?? null,
            urgency: $data['urgency'] ?? 'normal',
            sentiment: $data['sentiment'] ?? null,
            summary: $data['summary'] ?? null,
            replySuggestion: $data['reply_suggestion'] ?? null,
        );
    }
}
