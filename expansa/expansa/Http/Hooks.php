<?php

namespace Expansa\Http;

use Expansa\Http\Exception\InvalidArgument;
use Expansa\Http\Contracts\HookManager;
use Expansa\Http\Utility\InputValidator;

/**
 * Handles adding and dispatching events
 *
 * @package Expansa\Http
 */
class Hooks implements HookManager
{
    /**
     * Registered callbacks for each hook
     *
     * @var array
     */
    protected array $hooks = [];

    /**
     * Register a callback for a hook
     *
     * @param string   $hook     Hook name
     * @param callable $callback Function/method to call on event
     * @param int      $priority Priority number. <0 is executed earlier, >0 is executed later.
     * @throws InvalidArgument When the passed $priority argument is not an integer.
     */
    public function register(string $hook, callable $callback, int $priority = 0): void
    {
        if (InputValidator::isNumericArrayKey($priority) === false) {
            throw InvalidArgument::create(3, '$priority', 'integer', gettype($priority));
        }

        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [
                $priority => [],
            ];
        } elseif (!isset($this->hooks[$hook][$priority])) {
            $this->hooks[$hook][$priority] = [];
        }

        $this->hooks[$hook][$priority][] = $callback;
    }

    /**
     * Dispatch a message
     *
     * @param string $hook       Hook name
     * @param array  $parameters Parameters to pass to callbacks
     * @return bool Successfulness
     */
    public function dispatch(string $hook, array $parameters = []): bool
    {
        if (empty($this->hooks[$hook])) {
            return false;
        }

        if (!empty($parameters)) {
            // Strip potential keys from the array to prevent them being interpreted as parameter names in PHP 8.0.
            $parameters = array_values($parameters);
        }

        ksort($this->hooks[$hook]);

        foreach ($this->hooks[$hook] as $hooked) {
            foreach ($hooked as $callback) {
                $callback(...$parameters);
            }
        }

        return true;
    }
}
