<?php

declare(strict_types=1);

namespace Expansa\Ai\Exceptions;

use LogicException;

/**
 * Thrown when the request cannot accept another clarification answer.
 */
final class ClarificationUnavailable extends LogicException {}
