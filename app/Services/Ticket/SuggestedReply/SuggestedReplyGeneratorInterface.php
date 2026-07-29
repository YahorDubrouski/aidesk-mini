<?php

declare(strict_types=1);

namespace App\Services\Ticket\SuggestedReply;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyPassage;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;

interface SuggestedReplyGeneratorInterface
{
    /**
     * @param  list<SuggestedReplyPassage>  $passages
     */
    public function generate(string $question, array $passages): SuggestedReplyResult;
}
