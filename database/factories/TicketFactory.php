<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Ticket\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'public_id'       => (string) Str::ulid(),
            'user_id'         =>  User::factory(),
            'requester_email' => $this->faker->safeEmail(),
            'subject'         => $this->faker->optional()->sentence(6),
            'body'            => $this->faker->paragraph(),
            'status'          => TicketStatus::Open,
            'category'        => $this->faker->optional()->randomElement(['billing','technical','account']),
            'urgency'         => $this->faker->randomElement(['low','normal','high','critical']),
            'sentiment'       => $this->faker->optional()->randomElement(['negative','neutral','positive']),
            'product'         => $this->faker->optional()->randomElement(['core','pro','enterprise']),
            'summary'         => $this->faker->optional()->text(120),
            'answered_at'     => null,
        ];
    }
}
