<?php

declare(strict_types=1);

namespace App\Enums\Message;

enum MessageAuthorType: string
{
    case User  = 'user';
    case Agent = 'agent';
    case Ai    = 'ai';
}
