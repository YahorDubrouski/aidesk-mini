@extends('layouts.checkout')

@section('title', config('app.name').' — Checkout cancelled')

@section('content')
    <div class="checkout-icon checkout-icon--warn" aria-hidden="true">↩</div>
    <h1>Checkout cancelled</h1>
    <p class="checkout-lead" style="margin-bottom: 0;">No charge was made. You can return anytime to upgrade.</p>
@endsection

@section('footer')
    <a href="{{ url('/') }}">Home</a>
    <span>·</span>
    <a href="{{ route('checkout.upgrade') }}">Try again</a>
@endsection
