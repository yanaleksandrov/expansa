<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Contracts\Command as CommandContract;
use Expansa\Console\Terminal;
use Expansa\Console\Traits\WritesOutput;

/**
 * Base of a console command: the name, usage and options shown by "list" and "help", handle() runs it.
 * Terminal::addCommand() sets the terminal, the command reads its options and arguments from it.
 *
 * @package Expansa\Console\Commands
 */
abstract class AbstractCommand implements CommandContract
{
    use WritesOutput;

    /**
     * Name typed after artisan: `schedule:run`.
     */
    public string $name;

    /**
     * Group the "list" command shows the command in, `null` for the default one.
     */
    public ?string $group = null;

    /**
     * Usage shown by the "help" command.
     */
    public string $signature = 'command [options] -- [arguments]';

    /**
     * Options shown by the "help" command: `['-g' => 'Shows greeting.']`.
     *
     * @var array<string, string>
     */
    public array $options = [];

    /**
     * Inactive commands are hidden from the "list" command.
     */
    public bool $active = true;

    /**
     * Terminal running the command, set on registration.
     */
    public Terminal $console;

    /**
     * Get the translated description shown by "list" and "help".
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Run the command.
     *
     * @return void
     */
    abstract public function handle(): void;
}
