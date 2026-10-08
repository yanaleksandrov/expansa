<?php

declare(strict_types=1);

namespace Expansa\Access\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when no policy is registered for the class of a resource, its parents or interfaces.
 * A configuration error, unlike a denied ability.
 *
 * @package Expansa\Access
 */
final class PolicyNotFound extends InvalidArgumentException {}
