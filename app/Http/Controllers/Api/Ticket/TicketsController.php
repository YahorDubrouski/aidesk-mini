<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ticket;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Ticket\StoreTicketRequest;
use App\Http\Resources\Api\Ticket\ShowResource;
use App\Models\Ticket;
use OpenApi\Attributes as OA;

final class TicketsController extends Controller
{
    #[OA\Post(
        path: '/tickets',
        description: 'Creates a ticket; AI analysis may run asynchronously (see project docs).',
        summary: 'Create ticket',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'body'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'subject', type: 'string', maxLength: 160, nullable: true, example: 'Login issue'),
                    new OA\Property(
                        property: 'body',
                        description: 'Message body (10–5000 characters)',
                        type: 'string',
                        maxLength: 5000,
                        minLength: 10,
                        example: 'I cannot reset my password using the forgot-password link.'
                    ),
                ]
            )
        ),
        tags: ['Tickets'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Ticket created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'public_id', type: 'string', example: '01HZX...'),
                                new OA\Property(property: 'status', type: 'string', nullable: true),
                                new OA\Property(property: 'requester_email', type: 'string', format: 'email'),
                                new OA\Property(property: 'subject', type: 'string', nullable: true),
                                new OA\Property(property: 'body', type: 'string'),
                                new OA\Property(property: 'summary', type: 'string', nullable: true),
                                new OA\Property(property: 'product', type: 'string', nullable: true),
                                new OA\Property(property: 'urgency', type: 'string', nullable: true),
                                new OA\Property(property: 'sentiment', type: 'string', nullable: true),
                                new OA\Property(property: 'answered_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreTicketRequest $request): ShowResource
    {
        $ticket = Ticket::query()->create($request->validated());

        return ShowResource::make($ticket);
    }

    #[OA\Get(
        path: '/tickets/{ticket}',
        description: 'Loads a ticket by database id.',
        summary: 'Get ticket',
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'public_id', type: 'string'),
                                new OA\Property(property: 'status', type: 'string', nullable: true),
                                new OA\Property(property: 'requester_email', type: 'string', format: 'email'),
                                new OA\Property(property: 'subject', type: 'string', nullable: true),
                                new OA\Property(property: 'body', type: 'string'),
                                new OA\Property(property: 'summary', type: 'string', nullable: true),
                                new OA\Property(property: 'product', type: 'string', nullable: true),
                                new OA\Property(property: 'urgency', type: 'string', nullable: true),
                                new OA\Property(property: 'sentiment', type: 'string', nullable: true),
                                new OA\Property(property: 'answered_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Ticket $ticket): ShowResource
    {
        return ShowResource::make($ticket);
    }
}
