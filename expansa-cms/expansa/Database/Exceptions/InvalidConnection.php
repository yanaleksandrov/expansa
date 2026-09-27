<?php

declare(strict_types=1);

namespace Expansa\Database\Exceptions;

use InvalidArgumentException;

/**
 * Thrown by the Builder constructor when the connection options can't open a connection:
 * no driver or DSN, an unsupported PDO driver, a `pdo` option that is not a PDO object.
 *
 * @package Expansa\Database\Exceptions
 */
final class InvalidConnection extends InvalidArgumentException
{
}
