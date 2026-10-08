<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown by Manager::attempt() when a key used up its failed attempts: the check is not run.
 * A decision, not a failure: tell the user when to retry, e.g. with a 429 response.
 */
final class TooManyAttempts extends RuntimeException
{
    public function __construct(

        /**
         * Seconds until the next attempt is allowed.
         */
        public readonly int $retryAfter,
    ) {
        parent::__construct("Too many attempts, retry in $retryAfter seconds.");
    }
}
