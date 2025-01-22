<?php

declare(strict_types=1);

namespace Expansa\View\Exception;

use ErrorException;

/**
 * Exception class for view-related errors in the Expansa framework.
 *
 * This class extends the base ErrorException to provide a specific type of
 * exception for errors encountered within the view handling system.
 */
class ViewException extends ErrorException
{
}
