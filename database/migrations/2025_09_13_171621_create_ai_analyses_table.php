<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();

            // Link to the ticket this analysis belongs to
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            // Provider + model info
            $table->string('provider', 30)->default('openai')->index(); // e.g. openai, anthropic
            $table->string('model', 80)->index(); // e.g. gpt-4o-mini

            // Optional prompt/schema version so we can evolve prompts safely
            $table->unsignedSmallInteger('schema_version')->default(1);

            // Usage & cost (for dashboards, budgets)
            $table->unsignedInteger('usage_prompt_tokens')->default(0);
            $table->unsignedInteger('usage_completion_tokens')->default(0);
            $table->unsignedInteger('usage_total_tokens')->default(0);
            $table->decimal('cost_usd', 10, 4)->default(0); // store computed cost

            // Result payload (triage fields, moderation, suggested reply, etc.)
            // Keep raw-ish JSON to allow flexible post-processing.
            $table->json('result')->nullable();

            // Error info if the call failed (do not throw away failures)
            $table->string('error_code', 80)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['ticket_id', 'created_at'], 'ai_analyses_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
