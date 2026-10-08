<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use RuntimeException;

/**
 * Thrown when the AI service can not be reached or answers with an error status; the queue retries it.
 */
final class RequestFailed extends RuntimeException {}
