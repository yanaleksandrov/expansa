<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use Closure;
use InvalidArgumentException;
use LogicException;
use Expansa\Hooks\Attributes\Alias;
use Expansa\Hooks\Attributes\Priority;
use Expansa\Hooks\Exceptions\InvalidListener;
use Expansa\Support\Traits\FindsFiles;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Registers, sorts and fires hook listeners, the Hook facade instance.
 * Listeners run in ascending priority. A filter (call()) gives each the value returned by the previous one,
 * an action (run()) gives all of them the same arguments and ignores what they return.
 * The storage is static: every instance shares the listeners of the process.
 *
 * @package Expansa\Hooks
 */
final class Manager
{
    use FindsFiles;

    /**
     * Depth after which a hook is treated as re-triggering itself.
     */
    private const int MAX_RECURSION_DEPTH = 20;

    /**
     * Listeners by hook name and listener id.
     *
     * @var array<string, array<string, array{key: string, function: callable, source: array{file: string, line: int|string}, priority: int}>>
     */
    private static array $hooks = [];

    /**
     * Listeners sorted by priority, cached per hook until its listeners change.
     *
     * @var array<string, list<array{key: string, function: callable, source: array{file: string, line: int|string}, priority: int}>>
     */
    private static array $sorted = [];

    /**
     * Files of the listener classes already configured, by real path.
     *
     * @var array<string, true>
     */
    private static array $configuredFiles = [];

    /**
     * Listener class instances, created when one of their hooks fires first.
     *
     * @var array<class-string, object>
     */
    private static array $instances = [];

    /**
     * Number of call() and run() of each hook, with listeners or without.
     *
     * @var array<string, int>
     */
    private static array $callCounts = [];

    /**
     * Nesting of every hook running now, to catch a listener re-triggering its own hook.
     *
     * @var array<string, int>
     */
    private static array $depth = [];

    /**
     * Hooks queued by defer().
     *
     * @var list<array{0: string, 1: array}>
     */
    private static array $deferred = [];

    /**
     * Register every public method of the listener classes as a listener of the hook with its name.
     * A class configured before is skipped, it is created only when one of its hooks fires.
     *
     * @param class-string[]|string $listeners Classes, or a listener file or directory to scan.
     * @return void
     * @throws InvalidArgumentException|InvalidListener|ReflectionException
     */
    public function configure(string|array $listeners): void
    {
        if (is_array($listeners)) {
            foreach ($listeners as $class) {
                $this->configureClass($class);
            }

            return;
        }

        $paths = is_file($listeners) ? [$listeners] : $this->discover($listeners);

        foreach ($paths as $file) {
            // cheaper than diffing get_declared_classes() and works for a file loaded some other way
            if (! preg_match('/namespace\s+([^;]+);/', file_get_contents($file), $namespaceMatch)) {
                throw new InvalidListener("Listener file '$file' does not declare a namespace");
            }

            require_once $file;

            $this->configureClass($namespaceMatch[1] . '\\' . basename($file, '.php'));
        }
    }

    /**
     * Add a listener.
     *
     * @param string                $name
     * @param string|array|callable $function A function name, closure or `[object|class, method]`.
     * @param int                   $priority Ascending; #[Priority] of a method wins over it.
     * @param array{file: string, line: int|string}|null $source   Origin for flushSource() and `hooks:list`, the caller by default.
     * @param string|array|null     $identity What the listener id is computed from, `$function` by default.
     * @return void
     * @throws ReflectionException
     */
    public function add(
        string $name,
        string|array|callable $function,
        int $priority = Priority::BASE,
        ?array $source = null,
        string|array|null $identity = null,
    ): void {
        $id = $this->getId($name, $identity ?? $function);

        // #[Priority] targets methods only
        $priority = is_array($function) ? ($this->getPriority($function) ?? $priority) : $priority;

        if ($source === null) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $source    = [
                'file' => $backtrace[1]['file'] ?? '',
                'line' => $backtrace[1]['line'] ?? '',
            ];
        }

        self::$hooks[$name][$id] = [
            'key'      => $id,
            'function' => $function,
            'source'   => $source,
            'priority' => $priority,
        ];

        unset(self::$sorted[$name]);
    }

    /**
     * Add a listener that forgets itself after the first run.
     *
     * @param string   $name
     * @param callable $function
     * @param int      $priority
     * @return void
     * @throws ReflectionException
     */
    public function once(string $name, callable $function, int $priority = Priority::BASE): void
    {
        $wrapper = function (mixed $value = null, mixed ...$values) use ($name, $function, &$wrapper): mixed {
            $this->flush($name, $wrapper);

            return $function($value, ...$values);
        };

        $this->add($name, $wrapper, $priority);
    }

    public function has(string $name): bool
    {
        return isset(self::$hooks[$name]);
    }

    /**
     * Get the listeners of a hook sorted by priority, or of all hooks by name.
     *
     * @param string|null $name
     * @return array
     */
    public function get(?string $name = null): array
    {
        if ($name !== null) {
            return $this->getSorted($name);
        }

        $hooks = [];
        foreach (array_keys(self::$hooks) as $hookName) {
            $hooks[$hookName] = $this->getSorted($hookName);
        }

        return $hooks;
    }

    /**
     * Forget a listener of a hook, or the whole hook without `$function`.
     * A closure is found by its #[Alias] or by the same closure object.
     *
     * @param string                     $name
     * @param null|string|array|callable $function
     * @return bool Whether the listener existed.
     * @throws ReflectionException
     */
    public function flush(string $name, null|string|array|callable $function = null): bool
    {
        if ($function === null) {
            if (! isset(self::$hooks[$name])) {
                return false;
            }

            unset(self::$hooks[$name], self::$sorted[$name]);

            return true;
        }

        $id = $this->getId($name, $function);
        if (! isset(self::$hooks[$name][$id])) {
            return false;
        }

        $this->forgetListener($name, $id);

        return true;
    }

    /**
     * Forget every listener added from a file, or from any file in a directory: a plugin or a theme.
     * The files can be configured again by configure().
     *
     * @param string $path
     * @return int Number of forgotten listeners.
     */
    public function flushSource(string $path): int
    {
        $real    = realpath($path) ?: $path;
        $prefix  = is_dir($real) ? rtrim($real, '/\\') . DIRECTORY_SEPARATOR : null;
        $removed = 0;

        foreach (self::$hooks as $name => $listeners) {
            foreach ($listeners as $id => $hook) {
                $file = $hook['source']['file'];

                if ($prefix !== null ? ! str_starts_with($file, $prefix) : $file !== $real) {
                    continue;
                }

                $this->forgetListener($name, $id);
                unset(self::$configuredFiles[$file]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Filter: pass a value through the listeners in priority order, each returns the value for the next.
     * A listener that returns null for a value that was not null triggers a warning: it is an action
     * listener on a filter, the value after it is lost. Use run() for hooks whose result nobody reads.
     *
     * @param string $name
     * @param mixed  $value     Value to filter.
     * @param mixed  ...$values Extra arguments of the listeners.
     * @return mixed The filtered value.
     * @throws LogicException If the hook recurses deeper than MAX_RECURSION_DEPTH.
     */
    public function call(string $name, mixed $value = null, mixed ...$values): mixed
    {
        // the guard of enter() inlined: call() is the hot path
        self::$callCounts[$name] = (self::$callCounts[$name] ?? 0) + 1;

        if (! isset(self::$hooks[$name])) {
            return $value;
        }

        $depth = self::$depth[$name] ?? 0;
        if ($depth >= self::MAX_RECURSION_DEPTH) {
            $this->recursion($name);
        }
        self::$depth[$name] = $depth + 1;

        try {
            foreach ($this->getSorted($name) as $hook) {
                $result = ($hook['function'])($value, ...$values);
                if ($result === null && $value !== null) {
                    $source = $hook['source']['file'] . ':' . $hook['source']['line'];
                    trigger_error("A listener of the filter '$name' ($source) returned null, the value is lost", E_USER_WARNING);
                }

                $value = $result;
            }
        } finally {
            self::$depth[$name]--;
        }

        return $value;
    }

    /**
     * Action: run the listeners in priority order with the same arguments, their results are ignored.
     *
     * @param string $name
     * @param mixed  ...$args Arguments of every listener.
     * @return void
     * @throws LogicException If the hook recurses deeper than MAX_RECURSION_DEPTH.
     */
    public function run(string $name, mixed ...$args): void
    {
        if (! $this->enter($name)) {
            return;
        }

        try {
            foreach ($this->getSorted($name) as $hook) {
                ($hook['function'])(...$args);
            }
        } finally {
            self::$depth[$name]--;
        }
    }

    /**
     * Get the number of call() and run() of a hook in this process.
     *
     * @param string $name
     * @return int
     */
    public function calls(string $name): int
    {
        return self::$callCounts[$name] ?? 0;
    }

    /**
     * Run an action after the response is sent (fastcgi_finish_request() under PHP-FPM).
     *
     * @param string $name
     * @param mixed  ...$args Arguments of every listener.
     * @return void
     */
    public function defer(string $name, mixed ...$args): void
    {
        // a shutdown function can not be unregistered, so the flag is never reset
        static $shutdownRegistered = false;

        self::$deferred[] = [$name, $args];

        if ($shutdownRegistered) {
            return;
        }
        $shutdownRegistered = true;

        register_shutdown_function(function (): void {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            $queue          = self::$deferred;
            self::$deferred = [];

            foreach ($queue as [$name, $args]) {
                $this->run($name, ...$args);
            }
        });
    }

    /**
     * Forget every listener, configured file, listener instance and counter, e.g. between tests.
     *
     * @return void
     */
    public function reset(): void
    {
        self::$hooks           = [];
        self::$sorted          = [];
        self::$configuredFiles = [];
        self::$instances       = [];
        self::$callCounts      = [];
        self::$deferred        = [];
        self::$depth           = [];
    }

    /**
     * Register the public methods of a listener class, once per file.
     *
     * @param class-string $class
     * @return void
     * @throws InvalidListener|ReflectionException
     */
    private function configureClass(string $class): void
    {
        if (! class_exists($class)) {
            throw new InvalidListener("Listener class '$class' does not exist");
        }

        $reflection = new ReflectionClass($class);
        $file       = $reflection->getFileName() ?: $class;
        $realFile   = realpath($file) ?: $file;

        if (isset(self::$configuredFiles[$realFile])) {
            return;
        }
        self::$configuredFiles[$realFile] = true;

        if (! $reflection->isInstantiable()) {
            throw new InvalidListener("Listener class '$class' is not instantiable");
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (str_starts_with($methodName, '__')) {
                continue;
            }

            $this->add($methodName, $this->createListener($class, $methodName), $this->getPriority([$class, $methodName]) ?? Priority::BASE, [
                'file' => $reflection->getFileName() ?: '',
                'line' => $method->getStartLine() ?: '',
            ], [$class, $methodName]);
        }
    }

    /**
     * Create a listener that creates its class on the first run and reuses it.
     *
     * @param class-string $class
     * @param string       $method
     * @return Closure
     */
    private function createListener(string $class, string $method): Closure
    {
        return function (mixed $value = null, mixed ...$values) use ($class, $method): mixed {
            $instance = self::$instances[$class] ??= new $class();

            return $instance->$method($value, ...$values);
        };
    }

    /**
     * Get the listener id: a closure with #[Alias] is identified by the alias, so flush() finds it.
     *
     * @param string                $name
     * @param string|array|callable $function
     * @return string
     * @throws ReflectionException
     */
    private function getId(string $name, string|array|callable $function): string
    {
        $identity = match (true) {
            is_string($function)            => $function,
            $function instanceof Closure    => $this->getAlias($function) ?? (string) spl_object_id($function),
            is_object($function)            => (string) spl_object_id($function),
            is_object($function[0] ?? null) => spl_object_id($function[0]) . '::' . $function[1],
            default                         => implode('::', $function),
        };

        return hash('xxh3', $name . '::' . $identity);
    }

    /**
     * Count a run of a hook and mark it as running; the caller decrements the depth in `finally`.
     *
     * @param string $name
     * @return bool Whether the hook has listeners to run.
     * @throws LogicException If the hook recurses deeper than MAX_RECURSION_DEPTH.
     */
    private function enter(string $name): bool
    {
        self::$callCounts[$name] = (self::$callCounts[$name] ?? 0) + 1;

        if (! isset(self::$hooks[$name])) {
            return false;
        }

        $depth = self::$depth[$name] ?? 0;
        if ($depth >= self::MAX_RECURSION_DEPTH) {
            $this->recursion($name);
        }

        self::$depth[$name] = $depth + 1;

        return true;
    }

    /**
     * Stop a hook whose listener keeps re-triggering it.
     *
     * @param string $name
     * @return never
     * @throws LogicException
     */
    private function recursion(string $name): never
    {
        throw new LogicException(
            "Hook '$name' recursed more than " . self::MAX_RECURSION_DEPTH . ' levels deep - '
            . 'a listener is likely re-triggering the same hook it is running on'
        );
    }

    /**
     * Get the listeners of a hook sorted by priority, cached until they change.
     *
     * @param string $name
     * @return list<array{key: string, function: callable, source: array{file: string, line: int|string}, priority: int}>
     */
    private function getSorted(string $name): array
    {
        return self::$sorted[$name] ??= self::multisort(self::$hooks[$name] ?? [], 'priority');
    }

    /**
     * Sort a list of arrays by a key, keeping the order of equal ones; the keys are not kept.
     *
     * @param array  $array
     * @param string $key
     * @param bool   $descending
     * @return array
     */
    public static function multisort(array $array, string $key, bool $descending = false): array
    {
        usort($array, $descending
            ? static fn (array $a, array $b): int => $b[$key] <=> $a[$key]
            : static fn (array $a, array $b): int => $a[$key] <=> $b[$key]);

        return $array;
    }

    /**
     * Forget a listener and the hook when it was the last one, so has() stays accurate.
     *
     * @param string $name
     * @param string $id
     * @return void
     */
    private function forgetListener(string $name, string $id): void
    {
        unset(self::$hooks[$name][$id], self::$sorted[$name]);

        if (self::$hooks[$name] === []) {
            unset(self::$hooks[$name]);
        }
    }

    /**
     * Get the #[Alias] of a closure.
     *
     * @param Closure $function
     * @return string|null
     * @throws ReflectionException
     */
    private function getAlias(Closure $function): ?string
    {
        $attributes = new ReflectionFunction($function)->getAttributes(Alias::class);

        return $attributes === [] ? null : $attributes[0]->newInstance()->name;
    }

    /**
     * Get the #[Priority] of a method.
     *
     * @param array $method `[object|class, method]`.
     * @return int|null
     * @throws ReflectionException
     */
    private function getPriority(array $method): ?int
    {
        $attributes = new ReflectionMethod($method[0], $method[1])->getAttributes(Priority::class);

        return $attributes === [] ? null : $attributes[0]->newInstance()->priority;
    }
}
