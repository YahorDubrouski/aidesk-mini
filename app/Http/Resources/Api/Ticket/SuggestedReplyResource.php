<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Ticket;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\DTOs\Ticket\SuggestedReply\SuggestedReplySource;
use App\Http\Resources\BaseShowResource;
use Illuminate\Http\Request;

final class SuggestedReplyResource extends BaseShowResource
{
    /**
     * @var SuggestedReplyResult
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'answer' => $this->resource->answer,
            'refused' => $this->resource->refused,
            'refuse_reason' => $this->resource->refuseReason,
            'sources' => array_map(
                static fn (SuggestedReplySource $source): array => $source->toArray(),
                $this->resource->sources,
            ),
            'provider' => $this->resource->provider->value,
            'model' => $this->resource->model->value,
            'usage' => $this->resource->usage->toArray(),
        ];
    }
}
