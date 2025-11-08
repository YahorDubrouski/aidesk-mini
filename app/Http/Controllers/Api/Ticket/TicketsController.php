<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ticket;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Ticket\StoreTicketRequest;
use App\Http\Resources\Api\Ticket\ShowResource;
use App\Models\Ticket;

final class TicketsController extends Controller
{
    public function store(StoreTicketRequest $request): ShowResource
    {
        $ticket = Ticket::query()->create($request->validated());

        return ShowResource::make($ticket);
    }

    public function show(Ticket $ticket): ShowResource
    {
        return ShowResource::make($ticket);
    }
}
