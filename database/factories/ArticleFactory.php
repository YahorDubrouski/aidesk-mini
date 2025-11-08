<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Locale\Language;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);
        $bodyParagraphs = $this->faker->paragraphs(3);
        $body = implode("\n\n", $bodyParagraphs);

        return [
            'public_id' => (string) Str::ulid(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'body' => $body,
            'summary' => $this->faker->optional()->text(160),
            'language' => $this->faker->randomElement(Language::cases()),
            'is_published' => true,
            'embedding_version' => 1,
            'embedding_external_id' => null,
            'embedded_at' => null,
            'checksum_sha256' => hash('sha256', $body),
            'tags' => $this->faker->optional()->randomElements(
                ['getting-started', 'billing', 'troubleshooting', 'account', 'security', 'faq'],
                $this->faker->numberBetween(0, 3)
            ),
        ];
    }
}
