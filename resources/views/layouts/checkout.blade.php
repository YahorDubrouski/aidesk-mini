<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/css/checkout.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <div class="checkout-shell">
        <div class="@yield('column_class', 'checkout-column')">
        @yield('before_brand')
        <div class="checkout-brand-row">
            <div class="checkout-brand-lockup">
                <span class="checkout-logo-mark">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" decoding="async" />
                </span>
                <p class="checkout-brand-tag">@yield('brand_tag', 'Billing &amp; account')</p>
            </div>
        </div>
        <div class="checkout-card">
            @yield('content')
        </div>
        <div class="checkout-links">
            @yield('footer')
        </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
