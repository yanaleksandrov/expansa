<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Command;
use Expansa\Support\ClassMap;

/**
 * Writes the class map of autoload.php, so classes load without file checks. Run it after deploying or updating
 * the code: a class moved or deleted since the dump fails to load until the next dump, a new one loads without it.
 */
class AutoloadDump extends Command
{
    protected string $name = 'autoload:dump';

    protected string $description = 'Write the class map of the autoloader, or remove it with --clear.';

    protected string $signature = 'autoload:dump [--clear]';

    public function handle(): void
    {
        $file = EX_PATH . 'cache/classmap.php';

        if ($this->getConsole()->option('clear')) {
            if (is_file($file)) {
                unlink($file);
            }

            $this->info(t('The class map is removed, classes load by their namespace.'));

            return;
        }

        $classes = ClassMap::scan(EX_PATH);
        ClassMap::dump($file, $classes);

        $this->info(t('The class map of %d classes is written to cache/classmap.php.', count($classes)));
    }

    public function getDescription(): string
    {
        return t('Write the class map of the autoloader, or remove it with --clear.');
    }
}
