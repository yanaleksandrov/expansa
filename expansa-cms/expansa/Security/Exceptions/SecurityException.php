<?php

declare(strict_types=1);

namespace Expansa\Security\Exceptions;

use LogicException;

/**
 * Wrong use of the package, such as an unknown sanitizer rule.
 *
 * @package Expansa\Security
 */
class SecurityException extends LogicException {}
