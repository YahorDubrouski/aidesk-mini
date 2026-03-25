@extends('layouts.checkout')

@section('title', config('app.name').' — Sign in')

@section('content')
    <h1>Sign in</h1>
    <p class="checkout-lead">Use your account email and password.</p>

    <form method="post" action="{{ route('web.login', [], false) }}">
        @csrf
        @if (! empty($redirect))
            <input type="hidden" name="redirect" value="{{ $redirect }}">
        @endif
        <div class="checkout-field">
            <label class="checkout-label" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="checkout-input" required autocomplete="email" autofocus>
            @error('email')
                <p class="checkout-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="checkout-field">
            <label class="checkout-label" for="password">Password</label>
            <input id="password" name="password" type="password" class="checkout-input" required autocomplete="current-password">
        </div>
        <label style="font-size:0.8125rem;color:var(--checkout-muted);display:flex;align-items:center;gap:0.4rem;margin-bottom:0.75rem;">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button type="submit" class="checkout-btn">Sign in</button>
    </form>
    <div class="checkout-inline" style="margin-top:1.25rem;">
        <a href="{{ route('web.register', filled($redirect ?? null) ? ['redirect' => $redirect] : []) }}">Create account</a>
        <a href="{{ route('home') }}">← Home</a>
    </div>
@endsection

@section('footer')
    <a href="{{ route('home') }}">Home</a>
    @if (! empty($redirect ?? null))
        <span>·</span>
        <a href="{{ $redirect }}">Back</a>
    @endif
@endsection
