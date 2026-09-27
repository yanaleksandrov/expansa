<?php

declare(strict_types=1);

namespace Expansa\Cookie\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a cookie name contains characters other than letters, digits and `._-`.
 *
 * @package Expansa\Cookie
 */
final class InvalidName extends InvalidArgumentException {}
