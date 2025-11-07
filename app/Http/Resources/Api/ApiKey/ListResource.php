<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\ApiKey;

use Illuminate\Http\Resources\Json\ResourceCollection;

final class ListResource extends ResourceCollection
{
    public $collects = ShowResource::class;
}
