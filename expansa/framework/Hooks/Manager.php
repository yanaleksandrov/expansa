<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use Expansa\Support\Exception\PathfinderException;
use Expansa\Support\Pathfinder;
use ReflectionException;

class Manager extends HooksCollector
{
    use Pathfinder;

    /**
     * @throws PathfinderException|ReflectionException
     */
    public function configure(string $path): void
    {
        $paths = $this->discover($path);

        foreach ($paths as $path) {
            $fileContent = file_get_contents($path);

            $namespace = '';
            if (preg_match('/namespace\s+([^;]+);/', $fileContent, $namespaceMatches)) {
                $namespace = $namespaceMatches[1];
            }

            if (!$namespace) {
                throw new PathfinderException('The class must use a namespace');
            }
            $classname = basename($path, '.php');

            require_once $path;

            $reflection = new \ReflectionClass($namespace . '\\' . $classname);

            $methods  = $reflection->getMethods();
            $instance = $reflection->newInstance();

            foreach ($methods as $method) {
                $methodName = $method->getName();

                if ($method->isPublic()) {
                    $this->add($methodName, [$instance, $methodName]);
                }
            }
        }
    }

    /**
     * Adds a hook for a given name
     *
     * @param string                $name     Name of hook
     * @param string|array|callable $function
     * @param int                   $priority Hooks will be executed in order of priority in ascending order
     *
     * @return void
     * @throws ReflectionException
     */
    public function add(string $name, string|array|callable $function, int $priority = Priority::BASE): void
    {
        $id = $this->makeId($name, $function);

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        self::$hooks[$name][$id] = [
            'key'      => $id,
            'function' => $function,
            'source'   => [
                'file' => $backtrace[1]['file'] ?? '',
                'line' => $backtrace[1]['line'] ?? '',
            ],
            'priority' => $this->getProperty($function) ?: $priority,
        ];
    }

    /**
     * Checks if any hook exist for a given name
     *
     * @param string $name Name of hook
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset(self::$hooks[$name]);
    }

    /**
     * Return array of all hooks for all hooks, or of a given hook name
     *
     * @param string|null $name Name of hook
     *
     * @return array
     */
    public function get(?string $name = null): array
    {
        $hooks = $this->getHooks($name);

        return $this->multisort($hooks, 'priority');
    }

    /**
     * Removes hook from a given hook, if existing
     *
     * Note: Hooks using anonymous functions cannot be removed using this method
     *
     * @param string            $name     Name of hook
     * @param null|string|array $function Hook to remove
     *
     * @return bool Whether the hook existed
     * @throws ReflectionException
     */
    public function flush(string $name, null|string|array $function = null): bool
    {
        if ($function === null) {
            if (isset(self::$hooks[$name])) {
                unset(self::$hooks[$name]);

                return true;
            }
            return false;
        }

        $id = $this->makeId($name, $function);
        if (isset(self::$hooks[$name][$id])) {
            unset(self::$hooks[$name][$id]);

            return true;
        }
        return false;
    }

    /**
     * Filters value through queued hooks in order of priority
     *
     * @param  string $name      Name of hook
     * @param  mixed  $value     Original value to be filtered
     * @param  mixed  ...$values Additional values for callback
     * @return mixed             Filtered value
     */
    public function call(string $name, mixed $value = null, mixed ...$values): mixed
    {
        if (!isset(self::$hooks[$name])) {
            return $value;
        }

        $hooks = $this->multisort(self::$hooks[$name], 'priority');
        foreach ($hooks as $hook) {
            $value = call_user_func_array($hook['function'], [$value, ...$values]);
        }
        return $value;
    }
}
