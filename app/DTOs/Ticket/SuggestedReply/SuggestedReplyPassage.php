<?php

declare(strict_types=1);

namespace App\DTOs\Ticket\SuggestedReply;

final readonly class SuggestedReplyPassage
{
    public function __construct(
        public int $id,
        public string $title,
        public string $body,
        public ?float $similarity = null,
    ) {}
}
