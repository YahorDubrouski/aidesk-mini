<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Ticket;

use App\Http\Resources\BaseShowResource;
use App\Models\Ticket;
use Illuminate\Http\Request;

final class ShowResource extends BaseShowResource
{
    /**
     * @var Ticket
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->resource->public_id,
            'status' => $this->resource->status?->value,
            'requester_email' => $this->resource->requester_email,
            'subject' => $this->resource->subject,
            'body' => $this->resource->body,
            'summary' => $this->resource->summary,
            'product' => $this->resource->product,
            'urgency' => $this->resource->urgency,
            'sentiment' => $this->resource->sentiment,
            'answered_at' => $this->resource->answered_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
