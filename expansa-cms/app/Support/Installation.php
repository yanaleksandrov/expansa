<?php

declare(strict_types=1);

namespace App\Support;

use Expansa\Facades\Disk;
use Expansa\Http\Exceptions\ValidationException;

/**
 * Installation state: the installer writes env.php only after every other step succeeded.
 */
final class Installation
{
    public static function isComplete(string $root = EX_PATH): bool
    {
        return is_file($root . 'env.php');
    }

    /**
     * Write env.install.php from env.example.php with the placeholders replaced, e.g. 'db.name' => 'shop'.
     * A draft left by a failed attempt is replaced, so none of its old values survive.
     *
     * @param array<string, string> $values
     * @throws ValidationException
     */
    public static function draft(array $values, string $root = EX_PATH): string
    {
        $draft = $root . 'env.install.php';
        self::discard($draft);

        $env = Disk::file($root . 'env.example.php')->copy('env.install');
        if ($env->errors) {
            throw new ValidationException(t('Unable to write the environment configuration file.'), $env->errors);
        }

        Disk::file($draft)->rewrite($values);

        return $draft;
    }

    /**
     * Turn the draft into env.php, which marks the installation as complete.
     *
     * @throws ValidationException
     */
    public static function complete(string $draft, string $root = EX_PATH): void
    {
        if (! rename($draft, $root . 'env.php')) {
            throw new ValidationException(t('Unable to write the environment configuration file.'));
        }
    }

    public static function discard(string $draft): void
    {
        if (is_file($draft)) {
            unlink($draft);
        }
    }
}
