<?php

declare(strict_types=1);

namespace App\DTOs\Ai;

use App\Enums\Ai\AiModel;

final readonly class JsonCompletionResult
{
    /**
     * @param  array<string, mixed>|null  $decoded
     */
    public function __construct(
        public ?array $decoded,
        public ?string $rawContent,
        public UsageData $usage,
        public AiModel $model,
    ) {}
}
