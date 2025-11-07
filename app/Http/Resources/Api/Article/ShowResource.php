<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Article;

use App\Http\Resources\BaseShowResource;
use App\Models\Article;
use Illuminate\Http\Request;

final class ShowResource extends BaseShowResource
{
    /**
     * @var Article
     */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->resource->public_id,
            'title' => $this->resource->title,
            'slug' => $this->resource->slug,
            'summary' => $this->resource->summary,
            'language' => $this->resource->language->value,
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
