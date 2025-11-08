<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'AI-powered ticket triage & knowledge base search API',
    title: 'AIDesk Mini API'
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
abstract class Controller
{
    //
}
