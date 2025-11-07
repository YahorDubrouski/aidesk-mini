<?php

declare(strict_types=1);

namespace App\Services\Embedding;

use App\Services\Ai\AiClientInterface;

final readonly class EmbeddingService
{
    public function __construct(
        private AiClientInterface $aiClient
    ) {
    }

    public function generate(string $text): array
    {
        $embedding = $this->aiClient->embedText($text);
        return $embedding->vector;
    }
}
