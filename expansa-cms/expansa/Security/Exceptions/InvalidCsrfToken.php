<?php

declare(strict_types=1);

namespace Expansa\Security\Exceptions;

use UnexpectedValueException;

/**
 * Thrown when a submitted CSRF token is missing, forged, issued to another client or expired.
 *
 * @package Expansa\Security
 */
final class InvalidCsrfToken extends UnexpectedValueException {}
