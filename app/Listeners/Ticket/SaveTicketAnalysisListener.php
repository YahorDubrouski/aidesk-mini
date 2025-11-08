<?php

declare(strict_types=1);

namespace App\Listeners\Ticket;

use App\Events\Ticket\TicketAnalyzed;
use App\Services\Ticket\TicketAnalysisServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class SaveTicketAnalysisListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly TicketAnalysisServiceInterface $service
    ) {}

    public function handle(TicketAnalyzed $event): void
    {
        $this->service->saveAnalysisAndUpdateTicket($event->ticketId, $event->analysis);
    }
}
