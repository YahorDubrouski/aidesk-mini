<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Message\MessageAuthorType;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $authorType = $this->faker->randomElement(MessageAuthorType::cases());

        return [
            'ticket_id' => Ticket::factory(),
            'author_type' => $authorType,
            'author_id' => $authorType === MessageAuthorType::Ai ? null : User::factory(),
            'body' => $this->faker->paragraph(),
            'is_internal' => false,
            'meta' => null,
        ];
    }

    public function internal(): self
    {
        return $this->state(fn () => ['is_internal' => true]);
    }
}
