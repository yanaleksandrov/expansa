<?php

declare(strict_types=1);

namespace Expansa\Console;

use Expansa\Console\Commands\Env;
use Expansa\Console\Commands\Help;
use Expansa\Console\Commands\Index;
use Expansa\Console\Output\Writer;

/**
 * Class Console.
 *
 * @package cli
 */
class Terminal
{
	use Writer;

    /**
     * List of commands.
     *
     * @var array<string,Command> The command name as key and the object as value
     */
    protected array $commands = [];

    /**
     * The current command name.
     */
    protected string $command = '';

    /**
     * Input options.
     *
     * @var array<string,bool|string> The option value as string or TRUE if it
     * was passed without a value
     */
    protected array $options = [];

    /**
     * Input arguments.
     *
     * @var array<int,string>
     */
    protected array $arguments = [];

    /**
     * Console constructor.
     */
    public function __construct()
    {
        global $argv;
        $this->prepare($argv ?? []);
        $this->setDefaultCommands();
    }

    protected function setDefaultCommands() : static
    {
        if ($this->getCommand('list') === null) {
            $this->addCommand(new Index($this));
        }
        if ($this->getCommand('help') === null) {
            $this->addCommand(new Help($this));
        }
	    if ($this->getCommand('env') === null) {
		    $this->addCommand(new Env($this));
	    }
        return $this;
    }

    /**
     * Get all CLI options.
     *
     * @return array<string,bool|string>
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Get a specific option or null.
     *
     * @param string $option
     *
     * @return bool|string|null
     */
    public function getOption(string $option) : bool | string | null
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Get all arguments.
     *
     * @return array<int,string>
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }

    /**
     * Get a specific argument or null.
     *
     * @param int $position Argument position, starting from zero
     *
     * @return string|null The argument value or null if it was not set
     */
    public function getArgument(int $position) : ?string
    {
        return $this->arguments[$position] ?? null;
    }

    /**
     * Add a command to the console.
     *
     * @param Command|class-string<Command> $command A Command instance or the class FQN
     *
     * @return static
     */
    public function addCommand(Command | string $command) : static
    {
        if (is_string($command)) {
            $command = new $command();
        }
        $command->setConsole($this);
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    /**
     * Add many commands to the console.
     *
     * @param array<Command|class-string<Command>> $commands A list of Command
     * instances or the classes FQN
     *
     * @return static
     */
    public function addCommands(array $commands) : static
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }
        return $this;
    }

    /**
     * Get an active command.
     *
     * @param string $name Command name
     *
     * @return Command|null The Command on success or null if not found
     */
    public function getCommand(string $name) : ?Command
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }
        return null;
    }

    /**
     * Get a list of active commands.
     *
     * @return array<string,Command>
     */
    public function getCommands() : array
    {
        $commands = $this->commands;
        foreach ($commands as $name => $command) {
            if (!$command->isActive()) {
                unset($commands[$name]);
            }
        }
        ksort($commands);
        return $commands;
    }

    /**
     * Remove a command.
     *
     * @param string $name Command name
     *
     * @return static
     */
    public function removeCommand(string $name) : static
    {
        unset($this->commands[$name]);
        return $this;
    }

    /**
     * Remove commands.
     *
     * @param array<string> $names Command names
     *
     * @return static
     */
    public function removeCommands(array $names) : static
    {
        foreach ($names as $name) {
            $this->removeCommand($name);
        }
        return $this;
    }

    /**
     * Tells if it has a command.
     *
     * @param string $name Command name
     *
     * @return bool
     */
    public function hasCommand(string $name) : bool
    {
        return $this->getCommand($name) !== null;
    }

    /**
     * Run the Console.
     */
    public function handle() : void
    {
        if ($this->command === '') {
            $this->command = 'list';
        }

        $command = $this->getCommand($this->command);
        if ($command === null) {
	        $this->info(t('[red]#Command ":commandName" not found#', $this->command));
            return;
        }

        $command->handle();
    }

    public function exec(string $command) : void
    {
        $argumentValues = static::commandToArgs($command);
        array_unshift($argumentValues, 'removed');
        $this->prepare($argumentValues);
        $this->handle();
    }

    protected function reset() : void
    {
        $this->command = '';
        $this->options = [];
        $this->arguments = [];
    }

    /**
     * Prepare information of the command line.
     *
     * [options] [arguments] [options]
     * [options] -- [arguments]
     * [command]
     * [command] [options] [arguments] [options]
     * [command] [options] -- [arguments]
     * Short option: -l, -la === l = true, a = true
     * Long option: --list, --all=vertical === list = true, all = vertical
     * Only Long Options receive values:
     * --foo=bar or --f=bar - "foo" and "f" are bar
     * -foo=bar or -f=bar - all characters are true (f, o, =, b, a, r)
     * After -- all values are arguments, also if is prefixed with -
     * Without --, arguments and options can be mixed: -ls foo -x abc --a=e.
     *
     * @param array<int,string> $argumentValues
     */
    protected function prepare(array $argumentValues) : void
    {
        $this->reset();
        unset($argumentValues[0]);
        if (isset($argumentValues[1]) && $argumentValues[1][0] !== '-') {
            $this->command = $argumentValues[1];
            unset($argumentValues[1]);
        }
        $endOptions = false;
        foreach ($argumentValues as $value) {
            if ($endOptions === false && $value === '--') {
                $endOptions = true;
                continue;
            }
            if ($endOptions === false && $value[0] === '-') {
                if (isset($value[1]) && $value[1] === '-') {
                    $option = substr($value, 2);
                    if (str_contains($option, '=')) {
                        [$option, $value] = \explode('=', $option, 2);
                        $this->options[$option] = $value;
                        continue;
                    }
                    $this->options[$option] = true;
                    continue;
                }
                foreach (str_split(substr($value, 1)) as $item) {
                    $this->options[$item] = true;
                }
                continue;
            }
            //$endOptions = true;
            $this->arguments[] = $value;
        }
    }

    /**
     * @param string $command
     *
     * @see https://someguyjeremy.com/2017/07/adventures-in-parsing-strings-to-argv-in-php.html
     *
     * @return array<int,string>
     */
    public static function commandToArgs(string $command) : array
    {
        $charCount = strlen($command);
        $argv = [];
        $arg = '';
        $inDQuote = false;
        $inSQuote = false;
        for ($i = 0; $i < $charCount; $i++) {
            $char = $command[$i];
            if ($char === ' ' && !$inDQuote && !$inSQuote) {
                if ($arg !== '') {
                    $argv[] = $arg;
                }
                $arg = '';
                continue;
            }
            if ($inSQuote && $char === "'") {
                $inSQuote = false;
                continue;
            }
            if ($inDQuote && $char === '"') {
                $inDQuote = false;
                continue;
            }
            if ($char === '"' && !$inSQuote) {
                $inDQuote = true;
                continue;
            }
            if ($char === "'" && !$inDQuote) {
                $inSQuote = true;
                continue;
            }
            $arg .= $char;
        }
        $argv[] = $arg;
        return $argv;
    }
}
