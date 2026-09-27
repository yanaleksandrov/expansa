<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

/**
 * Shows the current environment.
 *
 * @package Expansa\Console
 */
final class Env extends AbstractCommand
{
    public string $name = 'env';

    public string $signature = 'env';

    public function getDescription(): string
    {
        return t('Display the current Expansa CMS environment');
    }

    public function handle(): void
    {
        $this->info(t('Current application environment: [green]#:env#', 'local'));

        $this->liveLine('Processing...');
        sleep(2);
        $this->liveLine('50% complete...');
        sleep(2);
        $this->liveLine('100% complete!', true);
    }
}
