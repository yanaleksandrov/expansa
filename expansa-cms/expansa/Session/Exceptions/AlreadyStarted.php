<?php

declare(strict_types=1);

namespace Expansa\Session\Exceptions;

use LogicException;

/**
 * Thrown when the session is started a second time.
 *
 * @package Expansa\Session
 */
final class AlreadyStarted extends LogicException {}
