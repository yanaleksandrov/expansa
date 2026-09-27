<?php

declare(strict_types=1);

namespace Expansa\Console;

use Expansa\Console\Commands\AbstractCommand;
use Expansa\Console\Commands\AutoloadDump;
use Expansa\Console\Commands\Env;
use Expansa\Console\Commands\Help;
use Expansa\Console\Commands\Index;
use Expansa\Console\Traits\WritesOutput;

/**
 * Commands registry and the parsed command line, the Terminal facade instance.
 * Commands of other packages and the app are added by bootstrap.php.
 *
 * @package Expansa\Console
 */
final class Terminal
{
    use WritesOutput;

    /**
     * Options of the command line: the value, or `true` for an option without one.
     *
     * @var array<string, bool|string>
     */
    public private(set) array $options = [];

    /**
     * Arguments of the command line.
     *
     * @var array<int, string>
     */
    public private(set) array $arguments = [];

    /**
     * Registered commands by name.
     *
     * @var array<string, AbstractCommand>
     */
    private array $commands = [];

    /**
     * Name of the command to run.
     */
    private string $command = '';

    public function __construct(

        /**
         * Application version shown by the "list" command.
         */
        public readonly string $version = '',
    ) {
        global $argv;

        $this->parse($argv ?? []);

        foreach ([Index::class, Help::class, Env::class, AutoloadDump::class] as $command) {
            $this->addCommand($command);
        }
    }

    /**
     * Get an option of the command line.
     *
     * @param string $option
     * @return bool|string|null
     */
    public function option(string $option): bool|string|null
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Get an argument of the command line.
     *
     * @param int $position Starting from zero.
     * @return string|null
     */
    public function argument(int $position): ?string
    {
        return $this->arguments[$position] ?? null;
    }

    /**
     * Add a command, a command with the same name is replaced.
     *
     * @param AbstractCommand|class-string<AbstractCommand> $command
     * @return static
     */
    public function addCommand(AbstractCommand|string $command): static
    {
        $command = is_string($command) ? new $command() : $command;

        $command->console = $this;

        $this->commands[$command->name] = $command;

        return $this;
    }

    /**
     * Add several commands.
     *
     * @param array<AbstractCommand|class-string<AbstractCommand>> $commands
     * @return static
     */
    public function addCommands(array $commands): static
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }

        return $this;
    }

    /**
     * Get a command, active or not.
     *
     * @param string $name
     * @return AbstractCommand|null
     */
    public function command(string $name): ?AbstractCommand
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * Get the active commands sorted by name.
     *
     * @return array<string, AbstractCommand>
     */
    public function commands(): array
    {
        $commands = array_filter($this->commands, fn (AbstractCommand $command) => $command->active);
        ksort($commands);

        return $commands;
    }

    /**
     * Forget a command.
     *
     * @param string $name
     * @return static
     */
    public function forgetCommand(string $name): static
    {
        unset($this->commands[$name]);

        return $this;
    }

    /**
     * Forget several commands.
     *
     * @param string[] $names
     * @return static
     */
    public function forgetCommands(array $names): static
    {
        foreach ($names as $name) {
            unset($this->commands[$name]);
        }

        return $this;
    }

    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Run the command of $argv, or the given command line: `help list`.
     *
     * @param string|null $command
     * @return void
     */
    public function run(?string $command = null): void
    {
        if ($command !== null) {
            $this->parse(['artisan', ...self::split($command)]);
        }

        $name    = $this->command !== '' ? $this->command : 'list';
        $handler = $this->command($name);

        if ($handler === null) {
            $this->info(t('[red]#Command ":commandName" not found#', $name));

            return;
        }

        $handler->handle();
    }

    /**
     * Parse the command line: `[command] [options] [arguments] [options]` or `[command] [options] -- [arguments]`.
     * `-la` sets `l` and `a` to true, `--all=vertical` sets `all`; after `--` everything is an argument.
     *
     * @param array<int, string> $argv With the script name first.
     * @return void
     */
    private function parse(array $argv): void
    {
        $this->command   = '';
        $this->options   = [];
        $this->arguments = [];

        unset($argv[0]);
        if (isset($argv[1]) && $argv[1] !== '' && $argv[1][0] !== '-') {
            $this->command = $argv[1];
            unset($argv[1]);
        }

        $endOptions = false;
        foreach ($argv as $value) {
            if (! $endOptions && $value === '--') {
                $endOptions = true;
                continue;
            }

            if ($endOptions || $value === '' || $value[0] !== '-') {
                $this->arguments[] = $value;
                continue;
            }

            if (isset($value[1]) && $value[1] === '-') {
                $option = substr($value, 2);

                if (str_contains($option, '=')) {
                    [$option, $value]       = explode('=', $option, 2);
                    $this->options[$option] = $value;
                } else {
                    $this->options[$option] = true;
                }
                continue;
            }

            foreach (str_split(substr($value, 1)) as $item) {
                $this->options[$item] = true;
            }
        }
    }

    /**
     * Split a command line into arguments, single and double quotes group words.
     *
     * @param string $command
     * @return array<int, string>
     */
    public static function split(string $command): array
    {
        $argv     = [];
        $arg      = '';
        $inDouble = false;
        $inSingle = false;

        for ($i = 0, $length = strlen($command); $i < $length; $i++) {
            $char = $command[$i];

            if ($char === ' ' && ! $inDouble && ! $inSingle) {
                if ($arg !== '') {
                    $argv[] = $arg;
                }
                $arg = '';
            } elseif ($char === "'" && ! $inDouble) {
                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle) {
                $inDouble = ! $inDouble;
            } else {
                $arg .= $char;
            }
        }

        $argv[] = $arg;

        return $argv;
    }
}
