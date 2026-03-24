<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Keys
    |--------------------------------------------------------------------------
    | Use test keys (pk_test_..., sk_test_...) for local development.
    | See https://dashboard.stripe.com/apikeys
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    | Signing secret for Stripe webhooks (e.g. checkout.session.completed).
    | Optional until webhooks are implemented. Get from Stripe CLI or Dashboard.
    |
    */

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Premium Price ID
    |--------------------------------------------------------------------------
    | Stripe Price ID (price_...) for the Premium line item — NOT the Product ID (prod_...).
    | Dashboard: Product catalog → open product → under Pricing copy the Price ID.
    |
    */

    'premium_price_id' => env('STRIPE_PREMIUM_PRICE_ID'),

];
