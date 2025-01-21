<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Command;

/**
 * Class Help.
 *
 * @package cli
 */
class Help extends Command
{
    protected string $name = 'help';

    protected string $signature = 'help [command_name]';

    protected string $description = 'Display help for the given command.';

    public function getDescription(): string
    {
        return t('Display help for the given command.');
    }

    public function handle(): void
    {
        $commandName = $this->console->getArgument(0) ?? 'help';

        $command = $this->console->getCommand($commandName);
        if ($command === null) {
            $this->error(
                t('Command ":commandName" not found', $commandName),
                defined('TESTING') ? null : 1
            );
            return;
        }

        $group = $command->getGroup();
        if ($group !== null) {
            $this->info(t('[green]#Group:#'));
            $this->info('   ' . $group . PHP_EOL);
        }

        $description = $command->getDescription();
        if ($description !== '') {
            $this->info(t('[green]#Description:#'));
            $this->info('   ' . $description . PHP_EOL);
        }

        $value = $command->getName();
        if ($value !== '') {
            $this->info(t('[green]#Usage:#'));
            $this->info('   ' . $value . PHP_EOL);
        }

        $options = $command->getOptions();
        if ($options) {
            $this->info(t('[green]#Options:#') . ':');

            krsort($options);
            foreach ($options as $option => $description) {
                $this->info(str_pad('   ' . $this->decorateOptions($option), 32) . $description);
            }
        }
        $this->newLine();
    }
}
