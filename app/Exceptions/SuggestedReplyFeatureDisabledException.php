<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SuggestedReplyFeatureDisabledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ticket suggested reply is disabled by feature flag.');
    }
}
