<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a provider name is not configured or its driver is unknown: a configuration error.
 */
final class ProviderNotFound extends InvalidArgumentException {}
