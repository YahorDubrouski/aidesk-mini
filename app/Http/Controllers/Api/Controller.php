<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'AI-powered ticket triage — Sanctum bearer token from POST /auth/login or /auth/register.',
    title: 'AIDesk Mini API'
)]
#[OA\Server(
    url: '/api',
    description: 'Laravel `routes/api.php` prefix'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    description: 'Use `Authorization: Bearer {token}` from login or register.',
    bearerFormat: 'Sanctum personal access token',
    scheme: 'bearer'
)]
abstract class Controller
{
    //
}
