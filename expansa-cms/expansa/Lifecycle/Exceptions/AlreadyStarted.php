<?php

declare(strict_types=1);

namespace Expansa\Lifecycle\Exceptions;

use LogicException;

/**
 * Thrown when the lifecycle is run a second time or a phase or context is declared after run().
 *
 * @package Expansa\Lifecycle\Exceptions
 */
final class AlreadyStarted extends LogicException {}
