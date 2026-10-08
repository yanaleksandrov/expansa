<?php

declare(strict_types=1);

namespace Expansa\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when the user declined the consent page or the provider refused the sign-in (`error`
 * in the callback). A decision, not a failure: send the user back to the sign-in page.
 */
final class Denied extends RuntimeException {}
