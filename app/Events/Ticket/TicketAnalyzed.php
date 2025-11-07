<?php

declare(strict_types=1);

namespace App\Events\Ticket;

use App\DTOs\Ai\TextAnalysisResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketAnalyzed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $ticketId,
        public TextAnalysisResult $analysis
    ) {
    }
}
