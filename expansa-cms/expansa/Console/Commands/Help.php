<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

/**
 * Shows the group, description, usage and options of a command.
 *
 * @package Expansa\Console
 */
final class Help extends AbstractCommand
{
    public string $name = 'help';

    public string $signature = 'help [<command>]';

    public function getDescription(): string
    {
        return t('Display help for the given command.');
    }

    public function handle(): void
    {
        $name    = $this->console->argument(0) ?? 'help';
        $command = $this->console->command($name);

        if ($command === null) {
            $this->error(t('Command ":commandName" not found', $name), defined('TESTING') ? null : 1);

            return;
        }

        if ($command->group !== null) {
            $this->info(t('[green]#Group:#'));
            $this->info('   ' . $command->group . PHP_EOL);
        }

        $description = $command->getDescription();
        if ($description !== '') {
            $this->info(t('[green]#Description:#'));
            $this->info('   ' . $description . PHP_EOL);
        }

        $this->info(t('[green]#Usage:#'));
        $this->info('   ' . $command->signature . PHP_EOL);

        if ($command->options !== []) {
            $this->info(t('[green]#Options:#'));

            $options = $command->options;
            krsort($options);
            foreach ($options as $option => $description) {
                $this->info(str_pad('   ' . $this->decorateOptions($option), 32) . $description);
            }
        }

        $this->newLine();
    }
}
