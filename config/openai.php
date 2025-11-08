<?php

use App\Enums\Ai\AiModel;
use App\Enums\Ai\AiProvider;

return [
    'enabled' => env('OPENAI_ENABLED', true),
    'fake' => env('OPENAI_FAKE', false),
    'provider' => env('AI_PROVIDER', AiProvider::OpenAI->value),
    'model' => env('AI_MODEL', AiModel::Gpt4oMini->value),
    'embedding_model' => env('AI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key' => env('OPENAI_API_KEY'),
    'timeout' => env('OPENAI_TIMEOUT', 15),
    'retry_times' => env('OPENAI_RETRY_TIMES', 2),
    'retry_sleep_ms' => env('OPENAI_RETRY_SLEEP_MS', 250),
];
