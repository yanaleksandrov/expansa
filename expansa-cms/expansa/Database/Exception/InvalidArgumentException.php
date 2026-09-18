<?php

declare(strict_types=1);

namespace Expansa\Database\Exception;

use Exception;

/**
 * The exception every invalid-argument error in this package throws - not SPL's own
 * `\InvalidArgumentException`, so callers (e.g. {@see \Expansa\Facades\Db::connection()}) can
 * catch just this package's own errors without also swallowing unrelated ones.
 */
class InvalidArgumentException extends Exception
{
}
