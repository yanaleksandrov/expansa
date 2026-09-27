<?php

declare(strict_types=1);

namespace Expansa\Lifecycle\Exceptions;

use LogicException;

/**
 * Thrown when a phase or context is declared under a name that is already taken.
 *
 * @package Expansa\Lifecycle\Exceptions
 */
final class AlreadyDeclared extends LogicException {}
