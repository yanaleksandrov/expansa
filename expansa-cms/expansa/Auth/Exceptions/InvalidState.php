<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when a callback doesn't match its sign-in attempt: no state, a foreign or expired one,
 * another provider or a missing code. A replayed or forged callback ends here.
 */
final class InvalidState extends RuntimeException {}
