<?php

declare(strict_types=1);

namespace App\Enums\Ai;

enum AiProvider: string
{
    case OpenAI    = 'openai';
    case Anthropic = 'anthropic';
}
