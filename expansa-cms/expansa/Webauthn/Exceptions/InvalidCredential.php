<?php

declare(strict_types=1);

namespace Expansa\Webauthn\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a browser credential fails a ceremony check: malformed data, a foreign origin
 * or challenge, a missing user verification, an invalid signature or a cloned authenticator.
 */
final class InvalidCredential extends InvalidArgumentException {}
