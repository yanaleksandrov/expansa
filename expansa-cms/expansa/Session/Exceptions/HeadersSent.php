<?php

declare(strict_types=1);

namespace Expansa\Session\Exceptions;

use RuntimeException;

/**
 * Thrown when the session cookie can't be sent because the output has already started.
 *
 * @package Expansa\Session
 */
final class HeadersSent extends RuntimeException {}
