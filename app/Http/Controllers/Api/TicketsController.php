<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Ticket\StoreTicketRequest;
use App\Http\Resources\Api\Ticket\ShowResource;
use App\Models\Ticket;

final class TicketsController extends Controller
{
    public function store(StoreTicketRequest $request): ShowResource
    {
        return ShowResource::make(Ticket::create($request->validated()));
    }

    public function show(Ticket $ticket): ShowResource
    {
        return ShowResource::make($ticket);
    }
}
