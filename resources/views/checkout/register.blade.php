@extends('layouts.checkout')

@section('title', config('app.name').' — Create account')

@section('content')
    <h1>Create account</h1>
    <p class="checkout-lead">Create a password, then you can continue where you left off.</p>

    <form method="post" action="{{ route('web.register.store', [], false) }}">
        @csrf
        @if (! empty($redirect ?? null))
            <input type="hidden" name="redirect" value="{{ $redirect }}">
        @endif
        <div class="checkout-field">
            <label class="checkout-label" for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" class="checkout-input" required autocomplete="name" autofocus>
            @error('name')
                <p class="checkout-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="checkout-field">
            <label class="checkout-label" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="checkout-input" required autocomplete="email">
            @error('email')
                <p class="checkout-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="checkout-field">
            <label class="checkout-label" for="password">Password</label>
            <input id="password" name="password" type="password" class="checkout-input" required autocomplete="new-password">
            @error('password')
                <p class="checkout-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="checkout-field">
            <label class="checkout-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="checkout-input" required autocomplete="new-password">
        </div>
        <button type="submit" class="checkout-btn">Create account</button>
    </form>
    <div class="checkout-inline" style="margin-top:1.25rem;">
        <a href="{{ route('login', filled($redirect ?? null) ? ['redirect' => $redirect] : []) }}">Already have an account? Sign in</a>
    </div>
@endsection

@section('footer')
    <a href="{{ route('home') }}">Home</a>
    @if (! empty($redirect ?? null))
        <span>·</span>
        <a href="{{ $redirect }}">Back</a>
    @endif
@endsection
