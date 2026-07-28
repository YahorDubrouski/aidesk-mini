<?php

declare(strict_types=1);

namespace App\Services\Ticket;

use App\DTOs\Ticket\SuggestedReplyResult;

interface SuggestedReplyGeneratorInterface
{
    /**
     * @param  list<array{id: int, title: string, body: string, similarity?: float|null}>  $passages
     */
    public function generate(string $question, array $passages): SuggestedReplyResult;
}
