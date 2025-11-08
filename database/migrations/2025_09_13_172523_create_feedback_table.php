<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('rating')->comment('1..5');
            $table->text('comment')->nullable();
            $table->boolean('used_suggestion')->default(false)->index();

            $table->timestamps();

            $table->index(['ticket_id', 'created_at'], 'feedback_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
