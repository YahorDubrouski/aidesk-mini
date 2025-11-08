<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\Ticket;
use App\Observers\ArticleObserver;
use App\Observers\TicketObserver;
use App\Services\Ai\AiClientInterface;
use App\Services\Ai\FakeAiClient;
use App\Services\Ai\OpenAiClient;
use App\Services\Ticket\TicketAnalysisService;
use App\Services\Ticket\TicketAnalysisServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiClientInterface::class, function () {
            if (
                !config('openai.enabled')
                || config('openai.fake')
                || app()->environment('testing')
            ) {
                return app()->make(FakeAiClient::class);
            }

            return app()->make(OpenAiClient::class);
        });

        $this->app->bind(TicketAnalysisServiceInterface::class, TicketAnalysisService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);
        Article::observe(ArticleObserver::class);
    }
}
