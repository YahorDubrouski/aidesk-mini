<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Locale\Language;
use App\Models\Article;
use App\Services\Embedding\ArticleEmbeddingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => 'How to Reset Your Password',
                'body' => 'If you forgot your password, click the "Forgot Password" link on the login page. Enter your email address and we will send you a reset link.',
                'language' => Language::English,
            ],
            [
                'title' => 'Setting Up Two-Factor Authentication',
                'body' => 'Two-factor authentication adds an extra layer of security to your account. Go to Settings > Security and enable 2FA. You can use an authenticator app or SMS.',
                'language' => Language::English,
            ],
            [
                'title' => 'Understanding API Rate Limits',
                'body' => 'API rate limits prevent abuse and ensure fair usage. Each API key has a daily quota. You can check your usage in the API Keys dashboard.',
                'language' => Language::English,
            ],
            [
                'title' => 'Creating Your First Ticket',
                'body' => 'To create a support ticket, navigate to the Tickets section and click "New Ticket". Fill in the subject and description, then submit. Our team will respond within 24 hours.',
                'language' => Language::English,
            ],
            [
                'title' => 'Managing Your Subscription',
                'body' => 'You can manage your subscription from the Account Settings page. Update your plan, payment method, or cancel your subscription at any time.',
                'language' => Language::English,
            ],
        ];

        foreach ($articles as $article) {
            Article::query()->create([
                'public_id' => (string) Str::ulid(),
                'title' => $article['title'],
                'slug' => Str::slug($article['title']),
                'body' => $article['body'],
                'summary' => Str::limit($article['body'], 100),
                'language' => $article['language'],
                'is_published' => true,
                'embedding_version' => ArticleEmbeddingService::EMBEDDING_VERSION,
            ]);
        }
    }
}
