<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Facades\Db;
use Expansa\Facades\Disk;
use Expansa\Filesystem\Exceptions\OperationFailed;
use Expansa\Http\Exceptions\ValidationFailed;

/**
 * Installation state: env.php with the settings, and the owner in the "site" option, which the installer writes last.
 * env.php without the owner, e.g. copied to a server with an empty database, opens the installer with its settings.
 */
final class Installation
{
    /**
     * Whether the installation finished: env.php is loaded and the database has the site owner.
     * An unreachable database throws instead of returning false, so a database failure never opens the installer.
     *
     * @param string $root Directory of env.php
     */
    public static function isComplete(string $root = EX_PATH): bool
    {
        return self::hasEnvironment($root)
            && defined('EX_DB')
            && Db::hasTable(EX_DB['prefix'] . 'options')
            && Option::get('site.owner.email', '') !== '';
    }

    /**
     * Whether env.php exists; the installer then takes the database settings from it instead of asking for them.
     *
     * @param string $root Directory of env.php
     */
    public static function hasEnvironment(string $root = EX_PATH): bool
    {
        return is_file($root . 'env.php');
    }

    /**
     * Write env.install.php from env.example.php with the placeholders replaced, e.g. 'db.name' => 'shop'.
     * A draft left by a failed attempt is replaced, so none of its old values survive.
     *
     * @param array<string, string> $values
     * @throws ValidationFailed
     */
    public static function draft(array $values, string $root = EX_PATH): string
    {
        $draft = $root . 'env.install.php';
        self::discard($draft);

        try {
            Disk::file($root . 'env.example.php')->copy('env.install')->replace($values);
        } catch (OperationFailed) {
            throw new ValidationFailed(t('Unable to write the environment configuration file.'));
        }

        return $draft;
    }

    /**
     * Turn the draft into env.php, which marks the installation as complete.
     *
     * @throws ValidationFailed
     */
    public static function complete(string $draft, string $root = EX_PATH): void
    {
        if (! rename($draft, $root . 'env.php')) {
            throw new ValidationFailed(t('Unable to write the environment configuration file.'));
        }
    }

    public static function discard(string $draft): void
    {
        if (is_file($draft)) {
            unlink($draft);
        }
    }
}
