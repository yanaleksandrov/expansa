<?php

declare(strict_types=1);

namespace Expansa\Hooks\Exceptions;

use Exception;

/**
 * Thrown on an invalid listener class or file, or on a hook that recurses into itself.
 *
 * @package Expansa\Hooks
 */
class HooksException extends Exception {}
