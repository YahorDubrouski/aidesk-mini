@extends('layouts.landing')

@section('title', config('app.name'))

@section('brand_tag', 'AI-powered ticket triage')

@section('content')
    <section class="landing-hero">
        <div class="landing-inner">
            <h1 class="landing-hero__title">AI-Powered Ticket Triage</h1>
            <p class="landing-hero__lead">
                Enterprise-grade backend API: AI integration, scalable architecture, API keys &amp; rate limits,
                queues and Horizon, Docker &amp; CI/CD, and solid code quality in one glance.
            </p>
            <div class="landing-hero__actions">
                @if (Route::has('checkout.upgrade'))
                    <a href="{{ route('checkout.upgrade', [], false) }}" class="landing-btn landing-btn--primary">Stripe Premium (demo)</a>
        @endif
                <a href="{{ url('/api/documentation') }}" class="landing-btn landing-btn--ghost">OpenAPI / Swagger</a>
                @guest
            @if (Route::has('login'))
                        <a href="{{ route('login', [], false) }}" class="landing-btn landing-btn--ghost">Sign in</a>
                        @endif
                @endguest
            </div>
                </div>
    </section>

    <section class="landing-section landing-section--strip" aria-labelledby="features-heading">
        <div class="landing-inner">
            <div class="landing-features landing-features--3">
                <article class="landing-feature-card">
                    <span class="landing-feature-card__label">AI</span>
                    <h3>Tickets &amp; search</h3>
                    <p>Ticket analysis (category, sentiment, urgency), content checks, and semantic article search with embeddings and cosine similarity — multi-provider AI client with retries.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-card__label">Auth &amp; API</span>
                    <h3>Sanctum &amp; API keys</h3>
                    <p>Token auth for web/API, full API key lifecycle with daily quotas, rate limiting, and request correlation IDs for tracing.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-card__label">Queues</span>
                    <h3>Horizon &amp; events</h3>
                    <p>Background jobs for analysis and embeddings, Laravel Horizon, event-driven listeners, and resilient retry behaviour.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-card__label">Architecture</span>
                    <h3>Domains &amp; services</h3>
                    <p>Domain-oriented structure, service layer, DTOs, observers, feature toggles, and an AI client abstraction for tests and swapping providers.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-card__label">DevOps</span>
                    <h3>Docker &amp; CI/CD</h3>
                    <p>Docker Compose (Sail), health endpoints for orchestration, GitHub Actions for tests and code style.</p>
                </article>
                <article class="landing-feature-card">
                    <span class="landing-feature-card__label">Quality</span>
                    <h3>Tests &amp; OpenAPI</h3>
                    <p>Automated tests, Laravel Pint, strict typing, Swagger generation, soft deletes and checksums for embeddings.</p>
                </article>
                </div>
        </div>
    </section>

    <section class="landing-section landing-section--compact" aria-labelledby="stack-heading">
        <div class="landing-inner">
            <header class="landing-section__head">
                <h2 id="stack-heading" class="landing-section__title">Tech stack (summary)</h2>
            </header>
            <ul class="landing-tech" role="list">
                <li>Laravel 12</li>
                <li>PHP 8.4</li>
                <li>MySQL 8</li>
                <li>Redis</li>
                <li>OpenAI API</li>
                <li>Horizon</li>
                <li>Sail</li>
                <li>Docker</li>
                <li>GitHub Actions</li>
                <li>Swagger / OpenAPI</li>
            </ul>
        </div>
    </section>
@endsection
