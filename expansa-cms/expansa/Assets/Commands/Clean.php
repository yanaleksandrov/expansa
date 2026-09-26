<?php

declare(strict_types=1);

namespace Expansa\Assets\Commands;

use Expansa\Assets\Manager;
use Expansa\Console\Command;

/**
 * Deletes cached, minified and combined asset files older than an age, optionally capping their total size.
 * Registered in bootstrap.php.
 *
 * @package Expansa\Assets\Commands
 */
final class Clean extends Command
{
    protected string $name = 'asset:clean';

    protected string $description = 'Remove cached/minified/combined asset files (cache/assets/*) older than a given age, and/or cap their total size.';

    protected string $signature = 'asset:clean [--max-age=<seconds>] [--max-size=<bytes>]';

    public function handle(): void
    {
        $maxAge  = (int) ($this->getConsole()->option('max-age') ?: 604800);
        $maxSize = $this->getConsole()->option('max-size');
        $maxSize = $maxSize !== null ? (int) $maxSize : null;

        $this->info(sprintf('Removed %d stale cached asset file(s).', Manager::clean($maxAge, $maxSize)));
    }
}
