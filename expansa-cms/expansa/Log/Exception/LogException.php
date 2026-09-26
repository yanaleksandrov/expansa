<?php

declare(strict_types=1);

namespace Expansa\Log\Exception;

use InvalidArgumentException;

/**
 * Thrown on an unknown level, an invalid channel configuration or a handler unable to write.
 *
 * @package Expansa\Log\Exception
 */
class LogException extends InvalidArgumentException {}
