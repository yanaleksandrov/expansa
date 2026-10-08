<?php

declare(strict_types=1);

namespace Expansa\Database\Exceptions;

use RuntimeException;

/**
 * Thrown by Schema when the database refuses a statement: a table, an index or a foreign key
 * that can't be created. The schema would be left half-built, so it is never ignored.
 *
 * @package Expansa\Database\Exceptions
 */
final class SchemaFailed extends RuntimeException {}
