<?php

declare(strict_types=1);

namespace Expansa\Assets\Commands;

use Expansa\Assets\Manager;
use Expansa\Console\Commands\AbstractCommand;

/**
 * Deletes cached, minified and combined asset files older than an age, optionally capping their total size.
 * Registered in bootstrap.php.
 *
 * @package Expansa\Assets\Commands
 */
final class Clean extends AbstractCommand
{
    public string $name = 'asset:clean';

    public string $signature = 'asset:clean [--max-age=<seconds>] [--max-size=<bytes>]';

    public function getDescription(): string
    {
        return t('Delete stale cached asset files.');
    }

    public function handle(): void
    {
        $maxAge   = (int) ($this->console->option('max-age') ?: 604800);
        $maxBytes = $this->console->option('max-size');

        $removed = Manager::clean($maxAge, $maxBytes !== null ? (int) $maxBytes : null);

        $this->info("Removed $removed stale cached asset file(s).");
    }
}
