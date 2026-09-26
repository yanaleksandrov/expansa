<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Assets\Manager;
use Expansa\Console\Command;

class AssetClean extends Command
{
    protected string $name = 'asset:clean';

    protected string $description = 'Remove cached/minified/combined asset files (cache/assets/*) older than a given age, and/or cap their total size.';

    protected string $signature = 'asset:clean [--max-age=<seconds>] [--max-size=<bytes>]';

    public function handle(): void
    {
        $maxAge  = (int) ($this->getConsole()->option('max-age') ?: 604800);
        $maxSize = $this->getConsole()->option('max-size');
        $maxSize = $maxSize !== null ? (int) $maxSize : null;

        $removed = Manager::clean($maxAge, $maxSize);

        $this->info(t('Removed :count stale cached asset file(s).', $removed));
    }

    public function getDescription(): string
    {
        return t('Remove cached/minified/combined asset files (cache/assets/*) older than a given age, and/or cap their total size.');
    }
}
