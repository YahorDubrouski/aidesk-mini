<?php

declare(strict_types=1);

return [
    /*
    | Trusted reverse proxies (Docker / Caddy). Use * in local dev so X-Forwarded-Proto is honored.
    | Must live in config (not bootstrap) so it works with php artisan config:cache.
    */
    'proxies' => env('TRUSTED_PROXIES', '*'),
];
