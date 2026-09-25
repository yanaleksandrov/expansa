<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Installation state: the installer writes env.php only after every other step succeeded.
 */
final class Installation
{
    public static function isComplete(): bool
    {
        return is_file(EX_PATH . 'env.php');
    }
}
