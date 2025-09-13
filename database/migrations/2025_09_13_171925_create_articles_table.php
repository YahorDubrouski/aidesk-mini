<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Public-safe identifier (shareable in URLs if needed)
            $table->ulid('public_id')->unique()->comment('Public article ULID');

            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->longText('body')->comment('Markdown content');

            // Optional short summary for previews/snippets
            $table->string('summary', 200)->nullable();

            // Language & publish controls
            $table->string('language', 5)->default('en')->index(); // e.g. en, pl
            $table->boolean('is_published')->default(true)->index();

            // Embedding/semantic search bookkeeping (Meilisearch/OpenAI job will fill)
            $table->unsignedSmallInteger('embedding_version')->default(1);
            $table->string('embedding_external_id', 191)->nullable()->index(); // doc id in search engine
            $table->timestamp('embedded_at')->nullable();
            $table->string('checksum_sha256', 64)->nullable()->comment('Detect content changes');

            // Free-form tags/metadata
            $table->json('tags')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_published', 'language', 'created_at'], 'articles_pub_lang_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
