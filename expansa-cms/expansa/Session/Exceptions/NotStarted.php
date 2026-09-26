<?php

declare(strict_types=1);

namespace Expansa\Session\Exceptions;

use LogicException;

/**
 * Thrown when the session id is regenerated before the session is started.
 *
 * @package Expansa\Session
 */
final class NotStarted extends LogicException {}
