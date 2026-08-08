<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_suggested_replies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->string('provider', 30)->default('openai')->index();
            $table->string('model', 80)->index();
            $table->unsignedSmallInteger('schema_version')->default(1);

            $table->text('answer');
            $table->boolean('refused')->default(false);
            $table->string('refuse_reason', 80)->nullable();
            $table->json('sources');

            $table->unsignedInteger('usage_prompt_tokens')->default(0);
            $table->unsignedInteger('usage_completion_tokens')->default(0);
            $table->unsignedInteger('usage_total_tokens')->default(0);
            $table->decimal('cost_usd', 10, 4)->default(0);

            $table->timestamps();

            $table->index(['ticket_id', 'created_at'], 'ticket_suggested_replies_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_suggested_replies');
    }
};
