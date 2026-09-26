<?php

declare(strict_types=1);

namespace App\Console;

use Expansa\Console\Command;

/**
 * Runs the site on PHP's built-in server through server.php, which routes requests like the .htaccess rules.
 */
final class Serve extends Command
{
    public string $name = 'serve';

    public string $signature = 'serve [--host=<host>] [--port=<port>]';

    public function handle(): void
    {
        $host = (string) ($this->console->option('host') ?: '127.0.0.1');
        $port = (int) ($this->console->option('port') ?: 8000);

        $this->info(t('Expansa is running on [green]#http://%s:%d#, press Ctrl+C to stop.', $host, $port));

        passthru(sprintf(
            '%s -S %s -t %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg("$host:$port"),
            escapeshellarg(EX_PATH),
            escapeshellarg(EX_PATH . 'server.php')
        ));
    }

    public function getDescription(): string
    {
        return t('Run the site on the PHP built-in web server.');
    }
}
