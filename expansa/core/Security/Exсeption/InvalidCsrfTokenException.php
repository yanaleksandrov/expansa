<?php

declare(strict_types=1);

namespace Expansa\Security\Exception;

use Exception;

/**
 * Exception thrown when a CSRF token is invalid.
 *
 * This exception is typically thrown during the verification of a CSRF token
 * to indicate that the token provided is either missing, expired, or does not match
 * the expected value.
 *
 * @package Expansa\Security
 */
class InvalidCsrfTokenException extends Exception {}
