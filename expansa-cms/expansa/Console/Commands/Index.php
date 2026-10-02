<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

/**
 * Shows the version and the active commands by group, the default command.
 *
 * @package Expansa\Console
 */
final class Index extends AbstractCommand
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
        $text = <<<'EOT'
         _____
        | ____|_  ___ __   __ _ _ __  ___  __ _
        |  _| \ \/ / '_ \ / _` | '_ \/ __|/ _` |
        | |___ >  <| |_) | (_| | | | \__ \ (_| |
        |_____/_/\_\ .__/ \__,_|_| |_|___/\__,_|
                   |_|

        EOT;

        $version = $this->console->version;
        $year    = date('Y');

        $this->info("[green]#$text#Program version: [green]#$version# | © 2025-$year «expansa.com»" . PHP_EOL);

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
