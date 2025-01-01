<?php

declare(strict_types=1);

namespace Grafema\Console;

use Grafema\Console\Contracts\Commands;
use Grafema\Console\Output\Writer;
use Grafema\I18n;

/**
 * Class Command.
 *
 * @package cli
 */
abstract class Command implements Commands
{
	use Writer;

    /**
     * Command name.
     */
    protected string $name;

    /**
     * Command group.
     */
    protected string $group;

    /**
     * Command description.
     */
    protected string $description;

    /**
     * Command usage.
     */
    protected string $signature = 'command [options] -- [arguments]';

    /**
     * Command options.
     *
     * @var array<string,string>
     */
    protected array $options = [];

    /**
     * Tells if command is active.
     */
    protected bool $active = true;

    /**
     * Command constructor.
     *
     * @param Terminal|null $console
     */
	public function __construct(
		protected ?Terminal $console = null
    ) {}

    /**
     * Get console instance.
     *
     * @return Terminal
     */
    public function getConsole(): Terminal
    {
        return $this->console;
    }

    /**
     * Set console instance.
     *
     * @param Terminal $console
     *
     * @return static
     */
    public function setConsole(Terminal $console) : static
    {
        $this->console = $console;
        return $this;
    }

    /**
     * Get command name.
     *
     * @return string
     */
    public function getName(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }
        $name = static::class;
        $pos  = strrpos($name, '\\');
        if ($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        if (str_ends_with($name, 'Command')) {
            $name = substr($name, 0, -7);
        }

        return $this->name = strtolower($name);
    }

    /**
     * Set command name.
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name) : static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get command group.
     *
     * @return string|null
     */
    public function getGroup(): ?string
    {
        return $this->group ?? null;
    }

    /**
     * Set command group.
     *
     * @param string $group
     *
     * @return static
     */
    public function setGroup(string $group) : static
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get command description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        if (isset($this->description)) {
            return $this->description;
        }
        return $this->description = I18n::_t('This command does not provide a description.');
    }

    /**
     * Set command description.
     *
     * @param string $description
     *
     * @return static
     */
    public function setDescription(string $description) : static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get command usage.
     *
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Set command usage.
     *
     * @param string $signature
     *
     * @return static
     */
    public function setSignature(string $signature) : static
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * Get command options.
     *
     * @return array<string,string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set command options.
     *
     * @param array<string,string> $options
     *
     * @return static
     */
    public function setOptions(array $options) : static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Tells if the command is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Activate the command.
     *
     * @return static
     */
    public function activate(): static
    {
        $this->active = true;
        return $this;
    }

    /**
     * Deactivate the command.
     *
     * @return static
     */
    public function deactivate(): static
    {
        $this->active = false;
        return $this;
    }
}
