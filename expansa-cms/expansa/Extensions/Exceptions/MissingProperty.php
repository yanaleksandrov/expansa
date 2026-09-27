<?php

declare(strict_types=1);

namespace Expansa\Extensions\Exceptions;

use UnexpectedValueException;

/**
 * Thrown when a loaded extension does not set a required property: name, description or version.
 * Manager skips such an extension.
 *
 * @package Expansa\Extensions\Exceptions
 */
final class MissingProperty extends UnexpectedValueException {}
