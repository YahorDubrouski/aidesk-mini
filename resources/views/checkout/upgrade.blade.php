@extends('layouts.checkout')

@section('title', config('app.name').' — Upgrade to Premium')

@section('content')
    <h1>Upgrade to Premium</h1>
    <p class="checkout-lead">Higher API quotas and extra features. Payment is processed securely by Stripe.</p>

    @if (session('error'))
        <p class="checkout-msg checkout-msg--spaced-bottom">{{ session('error') }}</p>
    @endif

    @auth
        <p class="checkout-lead checkout-lead--tight-bottom">
            Signed in as <strong>{{ Auth::user()->email }}</strong>
        </p>
        <form method="post" action="{{ route('checkout.premium.web', [], false) }}">
            @csrf
            <button type="submit" class="checkout-btn">Continue to secure checkout</button>
        </form>
        <div class="checkout-inline">
            <form method="post" action="{{ route('web.logout', [], false) }}" class="checkout-form--reset">
                @csrf
                <button type="submit" class="checkout-btn--bare">Sign out</button>
            </form>
            <a href="{{ url('/') }}">← Home</a>
        </div>
    @else
        <p class="checkout-lead checkout-lead--tight-bottom">
            Sign in to continue to checkout, or create an account first.
        </p>
        <div class="checkout-inline checkout-inline--guest-cta">
            <a href="{{ route('login', ['redirect' => '/upgrade']) }}" class="checkout-btn">Sign in</a>
            <a href="{{ route('web.register', ['redirect' => '/upgrade']) }}" class="checkout-account-link">Create account</a>
        </div>
        <p class="checkout-lead checkout-lead--api-hint">
            API clients can use <code>POST /api/checkout/premium</code> with a Bearer token.
        </p>
    @endauth
@endsection

@section('footer')
    <a href="{{ url('/') }}">Home</a>
@endsection
