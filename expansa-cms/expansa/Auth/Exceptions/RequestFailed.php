<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when a provider can't be reached, answers with an HTTP error or with something
 * other than the expected JSON.
 */
final class RequestFailed extends RuntimeException {}
