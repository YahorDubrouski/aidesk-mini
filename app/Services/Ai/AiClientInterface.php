<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\Ai\EmbeddingResult;
use App\DTOs\Ai\ModerationResult;
use App\DTOs\Ai\TextAnalysisResult;
use Illuminate\Http\Client\ConnectionException;

/**
 * Minimal AI client contract.
 */
interface AiClientInterface
{
    /**
     * Analyze text and return structured analysis data.
     */
    public function analyzeText(string $body, ?string $subject = null): TextAnalysisResult;

    /**
     * Return an embedding vector for semantic tasks.
     */
    public function embedText(string $text): EmbeddingResult;

    /**
     * Lightweight moderation gate.
     *
     * @throws ConnectionException
     */
    public function moderate(string $text): ModerationResult;
}
