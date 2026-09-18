<?php

declare(strict_types=1);

namespace Expansa\Console\Commands;

use Expansa\Console\Command;
use Expansa\Facades\Hook;

/**
 * Lists every registered hook so plugin/theme authors can see who listens to what without
 * grepping the codebase for Hook::add() calls.
 */
class HooksList extends Command
{
    protected string $name = 'hooks:list';

    protected string $description = 'List every registered hook, its listeners, their priority and where they were added.';

    protected string $signature = 'hooks:list [<name>]';

    public function handle(): void
    {
        $name  = $this->getConsole()->getArgument(0);
        $hooks = $name !== null ? [$name => Hook::get($name)] : Hook::get();

        $rows = [];
        foreach ($hooks as $hookName => $listeners) {
            foreach ($listeners as $listener) {
                $rows[] = [
                    $hookName,
                    $listener['priority'],
                    $this->describe($listener['function']),
                    $listener['source']['file'] !== ''
                        ? $listener['source']['file'] . ':' . $listener['source']['line']
                        : '-',
                    Hook::calls($hookName),
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
     * Renders a listener's callable as a short, human-readable label.
     *
     * @param string|array|callable $function The listener stored for a hook.
     *
     * @return string
     */
    private function describe(string|array|callable $function): string
    {
        return match (true) {
            is_string($function) => $function,
            is_array($function)  => (is_object($function[0]) ? $function[0]::class : $function[0]) . '::' . $function[1],
            default               => 'Closure',
        };
    }

    public function getDescription(): string
    {
        return t('List every registered hook, its listeners, their priority and where they were added.');
    }
}
