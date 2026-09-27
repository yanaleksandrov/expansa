<?php

declare(strict_types=1);

namespace Expansa\Hooks\Commands;

use Expansa\Console\Command;
use Expansa\Hooks\Manager;

/**
 * The `hooks:list` command: every hook with its listeners, their priority, origin and calls so far.
 * Named Index since `list` is a reserved word.
 *
 * @package Expansa\Hooks
 */
final class Index extends Command
{
    public string $name = 'hooks:list';

    public string $signature = 'hooks:list [<name>]';

    public function __construct(

        /**
         * Hooks to list, the storage is shared by all instances.
         */
        private readonly Manager $hooks = new Manager(),
    ) {}

    public function getDescription(): string
    {
        return t('List every registered hook, its listeners, their priority and where they were added.');
    }

    public function handle(): void
    {
        $name  = $this->console->argument(0);
        $hooks = $name !== null ? [$name => $this->hooks->get($name)] : $this->hooks->get();

        $rows = [];
        foreach ($hooks as $hookName => $listeners) {
            foreach ($listeners as $listener) {
                $rows[] = [
                    $hookName,
                    $listener['priority'],
                    $this->describe($listener['function']),
                    $listener['source']['file'] !== '' ? $listener['source']['file'] . ':' . $listener['source']['line'] : '-',
                    $this->hooks->calls($hookName),
                ];
            }
        }

        if ($rows === []) {
            $this->info($name !== null ? t('No listeners are registered for ":name".', $name) : t('No hooks are registered.'));

            return;
        }

        $this->table($rows, ['Hook', 'Priority', 'Listener', 'Registered at', 'Calls so far']);
    }

    /**
     * Describe a listener: the function name, `Class::method` or `Closure`.
     *
     * @param string|array|callable $function
     * @return string
     */
    private function describe(string|array|callable $function): string
    {
        return match (true) {
            is_string($function) => $function,
            is_array($function)  => (is_object($function[0]) ? $function[0]::class : $function[0]) . '::' . $function[1],
            default              => 'Closure',
        };
    }
}
