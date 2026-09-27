<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Command;

/**
 * Shows the version and the active commands by group, the default command.
 *
 * @package Expansa\Console
 */
final class Index extends Command
{
    public string $name = 'list';

    public string $signature = 'list [-g]';

    public function __construct()
    {
        $this->options = [
            '-g' => t('Shows greeting'),
        ];
    }

    public function getDescription(): string
    {
        return t('Shows full list of Expansa CLI commands.');
    }

    public function handle(): void
    {
        $text = <<<EOT
		   ____            __                      
		  / ___|_ __ __ _ / _| ___ _ __ ___   __ _ 
		 | |  _| '__/ _` | |_ / _ \ '_ ` _ \ / _` |
		 | |_| | | | (_| |  _|  __/ | | | | | (_| |
		  \____|_|  \__,_|_|  \___|_| |_| |_|\__,_|


		EOT;

        $version = $this->console->version;
        $year    = date('Y');

        $this->info("[green]#$text#Program version: [green]#$version# | © 2024-$year «expansa.com»" . PHP_EOL);

        if ($this->console->option('g')) {
            $this->info(t('Hello, friend!'));
        }

        $groupDefault = [];
        $groups       = [];
        foreach ($this->console->commands() as $name => $command) {
            if ($command->group === null) {
                $groupDefault[$name] = $command;
            } else {
                $groups[$command->group][$name] = $command;
            }
        }

        $this->info(t('[yellow]#Available Commands:#'));

        foreach ($groupDefault as $name => $command) {
            $this->info(str_pad("  [green]#$name#", 32) . $command->getDescription());
        }

        ksort($groups);

        foreach ($groups as $groupName => $commands) {
            $this->newLine();
            $this->info("  [yellow]#$groupName#");

            foreach ($commands as $name => $command) {
                $this->info(str_pad("  [green]#$name#", 32) . $command->getDescription());
            }
        }
        $this->newLine();
    }
}
