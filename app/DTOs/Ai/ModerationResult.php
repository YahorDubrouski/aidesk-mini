<?php

declare(strict_types=1);

namespace App\DTOs\Ai;

final readonly class ModerationResult
{
    public function __construct(
        public bool $flagged,
        public ?array $category = null,
        public ?string $reason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'flagged' => $this->flagged,
            'category' => $this->category,
            'reason' => $this->reason,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            flagged: (bool) ($data['flagged'] ?? false),
            category: $data['category'] ?? null,
            reason: $data['reason'] ?? null,
        );
    }
}
