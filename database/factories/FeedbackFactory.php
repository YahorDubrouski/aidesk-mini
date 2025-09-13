<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

final class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition(): array
    {
        return [
            'ticket_id'       => Ticket::factory(),
            'rating'          => $this->faker->numberBetween(1, 5),
            'comment'         => $this->faker->optional()->sentence(12),
            'used_suggestion' => $this->faker->boolean(60),
        ];
    }
}
