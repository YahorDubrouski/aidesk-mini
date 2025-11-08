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
        $hash = hexdec(substr(hash('sha1', $body.$subject), 0, 4));
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
                summary: mb_substr(trim($subject.' '.$body), 0, 120),
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
        // Generate deterministic vector based on text content
        // Use 1536 dimensions to match OpenAI's text-embedding-3-small
        $textLower = strtolower($text);
        $vector = [];
        $hash = hash('sha256', $textLower);

        // Extract keywords for semantic similarity
        $keywords = $this->extractKeywords($textLower);
        $baseVector = $this->generateBaseVector($keywords);

        // Generate 1536 dimensions with some semantic similarity
        for ($i = 0; $i < 1536; $i++) {
            $hashIndex = $i % 64;
            $hexValue = hexdec(substr($hash, $hashIndex, 1));
            $baseValue = $baseVector[$i % count($baseVector)] ?? 0.0;
            // Mix hash-based randomness with semantic base
            $vector[] = (float) (($baseValue * 0.7) + (($hexValue / 7.5 - 1.0) * 0.3));
        }

        return EmbeddingResult::fromArray([
            'model' => 'fake-embedding',
            'vector' => $vector,
            'usage' => ['tokens' => 1],
        ]);
    }

    private function extractKeywords(string $text): array
    {
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'what', 'which', 'who', 'where', 'when', 'why', 'how'];
        $words = preg_split('/\s+/', $text);

        return array_filter($words, fn ($word) => strlen($word) > 2 && ! in_array($word, $commonWords, true));
    }

    private function generateBaseVector(array $keywords): array
    {
        $vector = array_fill(0, 100, 0.0);
        foreach ($keywords as $keyword) {
            $hash = hash('sha256', $keyword);
            for ($i = 0; $i < 100; $i++) {
                $hexValue = hexdec(substr($hash, $i % 64, 1));
                $vector[$i] += (float) (($hexValue / 7.5) - 1.0) * 0.1;
            }
        }

        return $vector;
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
