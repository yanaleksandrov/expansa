<?php

declare(strict_types=1);

namespace Expansa\Patterns\Exception;

use Exception;

/**
 * Custom exception class for handling errors related to the Singleton pattern.
 * This exception can be thrown to signal issues specific to the use of Singletons,
 * such as initialization failures or misuse of the Singleton trait.
 */
class SingletonException extends Exception
{
}
