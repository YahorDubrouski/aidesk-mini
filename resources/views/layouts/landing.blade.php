<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="landing-page">
    <header class="landing-header">
        <div class="landing-inner landing-header__row">
            <a href="{{ url('/') }}" class="landing-brand">
                <span class="landing-logo-mark">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" decoding="async" />
                </span>
                <span class="landing-brand__meta">
                    <span class="landing-brand__tag">@yield('brand_tag', 'Helpdesk & API')</span>
                </span>
            </a>
            @if (Route::has('login'))
                <nav class="landing-nav" aria-label="Account">
                    @auth
                        <a href="{{ route('checkout.upgrade', [], false) }}">Premium checkout</a>
                        <form method="post" action="{{ route('web.logout', [], false) }}">
                            @csrf
                            <button type="submit">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login', [], false) }}">Sign in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('web.register', [], false) }}">Create account</a>
                        @endif
                        @if (Route::has('checkout.upgrade'))
                            <a href="{{ route('checkout.upgrade', [], false) }}">Pricing</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    <main class="landing-main">
        @yield('content')
    </main>

    <footer class="landing-footer">
        <div class="landing-inner landing-footer__row">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
            @hasSection('footer_extra')
                <span>@yield('footer_extra')</span>
            @endif
        </div>
    </footer>
</body>
</html>
