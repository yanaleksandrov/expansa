<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when an identity token fails a check: malformed, an unknown key, a bad signature,
 * a foreign issuer or audience, an expired token or another nonce.
 */
final class InvalidToken extends RuntimeException {}
