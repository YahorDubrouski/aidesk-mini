<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Ticket;

use App\Exceptions\SuggestedReplyFeatureDisabledException;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Ticket\StoreSuggestedReplyRequest;
use App\Http\Resources\Api\Ticket\SuggestedReplyResource;
use App\Models\Ticket;
use App\Services\Ticket\TicketSuggestedReplyService;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

final class SuggestedRepliesController extends Controller
{
    public function __construct(
        private readonly TicketSuggestedReplyService $ticketSuggestedReplyService,
    ) {}

    #[OA\Post(
        path: '/tickets/{ticket}/suggested-reply',
        description: 'Retrieves top knowledge articles by semantic search, then generates a grounded support reply with citations. Answers only from retrieved passages; refuses when context is insufficient.',
        summary: 'Generate suggested reply (RAG)',
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'limit',
                        description: 'Max articles to retrieve (default 5)',
                        type: 'integer',
                        maximum: 10,
                        minimum: 1,
                        example: 5,
                        nullable: true,
                    ),
                ]
            )
        ),
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(
                name: 'ticket',
                description: 'Ticket public id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: '01HZX...')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Grounded suggested reply',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'answer', type: 'string'),
                                new OA\Property(property: 'refused', type: 'boolean'),
                                new OA\Property(property: 'refuse_reason', type: 'string', nullable: true, example: 'empty_passages'),
                                new OA\Property(
                                    property: 'sources',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 12),
                                            new OA\Property(property: 'title', type: 'string', example: 'Password reset'),
                                            new OA\Property(property: 'similarity', type: 'number', format: 'float', nullable: true, example: 0.91),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(property: 'provider', type: 'string', example: 'openai'),
                                new OA\Property(property: 'model', type: 'string', example: 'gpt-4o-mini'),
                                new OA\Property(
                                    property: 'usage',
                                    properties: [
                                        new OA\Property(property: 'prompt_tokens', type: 'integer'),
                                        new OA\Property(property: 'completion_tokens', type: 'integer'),
                                        new OA\Property(property: 'total_tokens', type: 'integer'),
                                        new OA\Property(property: 'cost_usd', type: 'string', example: '0.0000'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Feature disabled'),
            new OA\Response(response: 404, description: 'Ticket not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreSuggestedReplyRequest $request, Ticket $ticket): SuggestedReplyResource
    {
        $limit = (int) ($request->validated('limit') ?? TicketSuggestedReplyService::DEFAULT_PASSAGE_LIMIT);

        try {
            $result = $this->ticketSuggestedReplyService->suggestForTicket($ticket->id, $limit);
        } catch (SuggestedReplyFeatureDisabledException $exception) {
            abort(Response::HTTP_FORBIDDEN, $exception->getMessage());
        }

        return SuggestedReplyResource::make($result);
    }
}
