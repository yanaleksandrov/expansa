<?php

declare(strict_types=1);

namespace Expansa\View\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a view name resolves to no template file.
 *
 * @package Expansa\View
 */
final class ViewNotFound extends InvalidArgumentException {}
