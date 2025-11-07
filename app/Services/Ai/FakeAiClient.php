<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\DTOs\Ai\EmbeddingResult;
use App\DTOs\Ai\ModerationResult;
use App\DTOs\Ai\TextAnalysisData;
use App\DTOs\Ai\TextAnalysisResult;
use App\DTOs\Ai\UsageData;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use App\Enums\Ticket\TicketSentiment;
use App\Enums\Ticket\TicketUrgency;

final class FakeAiClient implements AiClientInterface
{
    public function analyzeText(string $body, ?string $subject = null): TextAnalysisResult
    {
        $hash = hexdec(substr(hash('sha1', $body . $subject), 0, 4));
        $urgencyCases = TicketUrgency::cases();
        $sentimentCases = TicketSentiment::cases();
        $urgency = $urgencyCases[$hash % count($urgencyCases)]->value;
        $sentiment = $sentimentCases[$hash % count($sentimentCases)]->value;

        return new TextAnalysisResult(
            provider: AiProvider::OpenAI,
            model: AiModel::Gpt4oMini,
            result: new TextAnalysisData(
                category: ['billing', 'technical', 'account'][$hash % 3],
                urgency: $urgency,
                sentiment: $sentiment,
                summary: mb_substr(trim($subject . ' ' . $body), 0, 120),
                replySuggestion: 'Thanks for reaching out - here is a suggested reply...',
            ),
            usage: new UsageData(
                promptTokens: 50,
                completionTokens: 40,
                totalTokens: 90,
                costUsd: '0.0000',
            ),
        );
    }

    public function embedText(string $text): EmbeddingResult
    {
        // Return small fixed-length vector
        $seed = substr(hash('sha1', $text), 0, 16);
        $vector = [];
        for ($i = 0; $i < 16; $i++) {
            $vector[] = (float)((hexdec($seed[$i]) % 10) / 10);
        }

        return EmbeddingResult::fromArray([
            'model' => 'fake-embedding',
            'vector' => $vector,
            'usage' => ['tokens' => 1],
        ]);
    }

    public function moderate(string $text): ModerationResult
    {
        $isFlagged = stripos($text, 'abuse') !== false;
        return new ModerationResult(
            flagged: $isFlagged,
            category: $isFlagged ? ['abuse' => true] : null,
            reason: null,
        );
    }
}
