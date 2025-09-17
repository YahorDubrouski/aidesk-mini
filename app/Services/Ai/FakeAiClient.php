<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use App\Enums\Ticket\TicketSentiment;
use App\Enums\Ticket\TicketUrgency;

final class FakeAiClient implements AiClientInterface
{
    public function analyzeTicket(string $body, ?string $subject = null): array
    {
        $hash = hexdec(substr(hash('sha1', $body . $subject), 0, 4));
        $urgCases = TicketUrgency::cases();
        $senCases = TicketSentiment::cases();
        $urgency = $urgCases[$hash % count($urgCases)]->value;
        $sentiment = $senCases[$hash % count($senCases)]->value;

        return [
            'provider' => AiProvider::OpenAI->value,
            'model' => AiModel::Gpt4oMini->value,
            'result' => [
                'category' => ['billing', 'technical', 'account'][$hash % 3],
                'urgency' => $urgency,
                'sentiment' => $sentiment,
                'summary' => mb_substr(trim($subject . ' ' . $body), 0, 120),
                'reply_suggestion' => 'Thanks for reaching out - here is a suggested reply...',
            ],
            'usage' => [
                'prompt_tokens' => 50,
                'completion_tokens' => 40,
                'total_tokens' => 90,
                'cost_usd' => '0.0000',
            ],
        ];
    }

    public function embedText(string $text): array
    {
        // Return small fixed-length vector
        $seed = substr(hash('sha1', $text), 0, 16);
        $vector = [];
        for ($i = 0; $i < 16; $i++) {
            $vector[] = (float)((hexdec($seed[$i]) % 10) / 10);
        }

        return [
            'model' => 'fake-embedding',
            'vector' => $vector,
            'usage' => ['tokens' => 1],
        ];
    }

    public function moderate(string $text): array
    {
        $flag = stripos($text, 'abuse') !== false;
        return ['flagged' => $flag, 'category' => $flag ? 'abuse' : null, 'reason' => null];
    }
}
