<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;

/**
 * Minimal AI client contract.
 */
interface AiClientInterface
{
    /**
     * Analyze a ticket's free text and return triage fields.
     *
     * @return array{
     *   provider: string,
     *   model: string,
     *   result: array{
     *     category: string|null,
     *     urgency: string,         // low|normal|high|critical
     *     sentiment: string|null,  // negative|neutral|positive
     *     summary: string|null,
     *     reply_suggestion: string|null
     *   },
     *   usage: array{
     *     prompt_tokens:int,
     *     completion_tokens:int,
     *     total_tokens:int,
     *     cost_usd: string
     *   }
     * }
     */
    public function analyzeTicket(string $body, ?string $subject = null): array;

    /**
     * Return an embedding vector for semantic tasks.
     *
     * @return array{model:string, vector: float[], usage: array{tokens:int}}
     */
    public function embedText(string $text): array;

    /**
     * Lightweight moderation gate.
     * @return array{flagged: bool, category?: string|null, reason?: string|null}
     * @throws ConnectionException
     */
    public function moderate(string $text): array;
}
