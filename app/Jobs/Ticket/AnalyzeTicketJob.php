<?php

declare(strict_types=1);

namespace App\Jobs\Ticket;

use App\Services\Ticket\TicketAnalysisServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class AnalyzeTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $ticketId) {}

    /**
     * @throws ConnectionException
     */
    public function handle(TicketAnalysisServiceInterface $service): void
    {
        $service->analyze($this->ticketId);
    }
}
