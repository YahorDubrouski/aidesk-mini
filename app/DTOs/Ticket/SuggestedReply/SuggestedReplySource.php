<?php

declare(strict_types=1);

namespace App\DTOs\Ticket\SuggestedReply;

final readonly class SuggestedReplySource
{
    public function __construct(
        public int $id,
        public string $title,
        public ?float $similarity = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'similarity' => $this->similarity,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            title: (string) ($data['title'] ?? ''),
            similarity: isset($data['similarity']) ? (float) $data['similarity'] : null,
        );
    }
}
