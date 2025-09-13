<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Public-safe identifier (no sequential leakage)
            $table->ulid('public_id')->unique()->comment('Public ticket ULID');

            // Optional link to registered user; allow null to support anonymous intake
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->comment('Owner user if authenticated; null for anonymous');

            // Intake fields
            $table->string('requester_email')->index()->comment('Email provided in intake');
            $table->string('subject', 160)->nullable()->comment('Optional short subject');
            $table->text('body')->comment('Original user message');

            // AI triage fields (strings, validated in app layer)
            $table->string('status', 20)->default('open')->index()->comment('open|answered|closed');
            $table->string('category', 50)->nullable()->index()->comment('AI/extracted category');
            $table->string('urgency', 20)->default('normal')->index()->comment('low|normal|high|critical');
            $table->string('sentiment', 20)->nullable()->index()->comment('negative|neutral|positive');
            $table->string('product', 50)->nullable()->index()->comment('Optional product tag');
            $table->string('summary', 180)->nullable()->comment('Short AI summary (<= ~120 chars)');

            // Timeline helpers
            $table->timestamp('answered_at')->nullable()->comment('When an agent reply was sent');

            $table->softDeletes();
            $table->timestamps();

            // Common query pattern
            $table->index(['status', 'created_at'], 'tickets_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
