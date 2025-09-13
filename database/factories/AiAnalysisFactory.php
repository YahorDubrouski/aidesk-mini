<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;
use App\Models\AiAnalysis;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

final class AiAnalysisFactory extends Factory
{
    protected $model = AiAnalysis::class;

    public function definition(): array
    {
        $prompt = $this->faker->numberBetween(50, 500);
        $completion = $this->faker->numberBetween(30, 400);

        return [
            'ticket_id'                => Ticket::factory(),
            'provider'                 => AiProvider::OpenAI,
            'model'                    => AiModel::Gpt4oMini,
            'schema_version'           => 1,
            'usage_prompt_tokens'      => $prompt,
            'usage_completion_tokens'  => $completion,
            'usage_total_tokens'       => $prompt + $completion,
            'cost_usd'                 => $this->faker->randomFloat(4, 0.0001, 0.50),
            'result'                   => ['summary' => $this->faker->text(100)],
            'error_code'               => null,
            'error_message'            => null,
        ];
    }
}
