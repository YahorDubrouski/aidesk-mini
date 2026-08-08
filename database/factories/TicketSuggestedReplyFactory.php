<?php

declare(strict_types=1);

namespace Database\Factories;

use App\DTOs\Ticket\SuggestedReply\SuggestedReplyResult;
use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use App\Models\Ticket;
use App\Models\TicketSuggestedReply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSuggestedReply>
 */
final class TicketSuggestedReplyFactory extends Factory
{
    protected $model = TicketSuggestedReply::class;

    public function definition(): array
    {
        $prompt = $this->faker->numberBetween(50, 500);
        $completion = $this->faker->numberBetween(30, 400);

        return [
            'ticket_id' => Ticket::factory(),
            'provider' => AiProvider::OpenAI,
            'model' => AiModel::Gpt4oMini,
            'schema_version' => 1,
            'answer' => $this->faker->sentence(),
            'refused' => false,
            'refuse_reason' => null,
            'sources' => [
                [
                    'id' => 1,
                    'title' => 'Example article',
                    'similarity' => 0.9,
                ],
            ],
            'usage_prompt_tokens' => $prompt,
            'usage_completion_tokens' => $completion,
            'usage_total_tokens' => $prompt + $completion,
            'cost_usd' => '0.0000',
        ];
    }

    public function refused(): self
    {
        return $this->state(fn (): array => [
            'answer' => SuggestedReplyResult::DEFAULT_REFUSE_ANSWER,
            'refused' => true,
            'refuse_reason' => SuggestedReplyResult::REFUSE_REASON_EMPTY_PASSAGES,
            'sources' => [],
            'usage_prompt_tokens' => 0,
            'usage_completion_tokens' => 0,
            'usage_total_tokens' => 0,
        ]);
    }
}
