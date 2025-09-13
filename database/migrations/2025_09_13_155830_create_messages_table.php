<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Message belongs to a ticket
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            // Optional link to a user (for user/agent authors). AI messages can be null.
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Who wrote it: end-user, support agent, or AI system
            $table->enum('author_type', ['user', 'agent', 'ai'])->index();

            // Message body (required)
            $table->text('body');

            // Internal notes not visible to requester
            $table->boolean('is_internal')->default(false)->index();

            // Optional JSON metadata (e.g., tokens, model, attachments)
            $table->json('meta')->nullable();

            $table->timestamps();

            // Common query patterns
            $table->index(['ticket_id', 'created_at'], 'messages_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
