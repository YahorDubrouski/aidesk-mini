@extends('layouts.checkout')

@section('title', config('app.name').' — Thank you')

@section('content')
    @if ($order && $order->isPaid())
        <div class="checkout-icon checkout-icon--success" aria-hidden="true">✓</div>
        <h1>Payment successful</h1>
        <p class="checkout-status"><strong>Premium is active.</strong> You can use upgraded features and quotas.</p>
        <p class="checkout-lead" style="margin-bottom: 0;">Thank you for your purchase.</p>
    @else
        <div class="checkout-icon checkout-icon--warn" aria-hidden="true">…</div>
        <h1>Thank you</h1>
        <p class="checkout-lead" style="margin-bottom: 0;">We’re confirming your payment. If you just completed checkout, Premium will activate in a moment.</p>
    @endif
@endsection

@section('footer')
    <a href="{{ url('/') }}">Home</a>
    <span>·</span>
    <a href="{{ route('checkout.upgrade') }}">Upgrade again</a>
@endsection
