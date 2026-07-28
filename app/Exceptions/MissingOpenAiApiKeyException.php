<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class MissingOpenAiApiKeyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'OpenAI API key is required when OPENAI_FAKE is disabled. Set OPENAI_API_KEY or enable OPENAI_FAKE=true.'
        );
    }
}
