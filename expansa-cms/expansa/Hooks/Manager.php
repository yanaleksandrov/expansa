<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use Closure;
use Expansa\Hooks\Exception\HooksException;
use Expansa\Support\Exception\FinderException;
use Expansa\Support\Finder;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Registers, sorts and fires hook listeners: named extension points that let application code
 * observe or filter a value without the caller knowing who, if anyone, is listening.
 */
final class Manager extends HooksCollector
{
    use Finder;

    /**
     * Files already scanned by configure(), keyed by their real path.
     *
     * Files of the listener classes configured in this request: without it, configuring the same
     * class twice would register its methods again, so every matching hook would fire twice.
     *
     * @var array<string, true>
     */
    private static array $configuredFiles = [];

    /**
     * Listener class instances discovered by configure(), keyed by class name and created lazily
     * the first time one of their hooks actually fires - not while configure() is still scanning.
     *
     * @var array<class-string, object>
     */
    private static array $instances = [];

    /**
     * Number of times each hook has actually been fired via call(), regardless of how many
     * listeners it has (or had none at all).
     *
     * @var array<string, int>
     */
    private static array $callCounts = [];

    /**
     * Hooks queued by defer(), waiting to run once the response has been sent.
     *
     * @var list<array{0: string, 1: mixed, 2: array}>
     */
    private static array $deferred = [];

    /**
     * A hook recursing (directly or through a chain of other hooks) more than this many levels
     * deep is almost certainly a listener accidentally re-triggering its own hook, not a
     * legitimate use - call() aborts with a HooksException instead of running until the PHP
     * call stack overflows.
     */
    private const int MAX_RECURSION_DEPTH = 20;

    /**
     * Registers every public method of each listener class as a listener for the hook matching its name.
     * Pass the classes explicitly, or a file or directory to scan; a class configured before is skipped.
     * A listener class is only instantiated the first time one of its hooks actually fires.
     *
     * @param class-string[]|string $listeners Listener classes, or a listener file or directory to scan.
     *
     * @return void
     * @throws FinderException|HooksException|ReflectionException
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
            if (!preg_match('/namespace\s+([^;]+);/', file_get_contents($file), $namespaceMatch)) {
                throw new HooksException("Listener file '$file' does not declare a namespace");
            }

            require_once $file;

            $this->configureClass($namespaceMatch[1] . '\\' . basename($file, '.php'));
        }
    }

    /**
     * @param class-string $class
     *
     * @throws HooksException|ReflectionException
     */
    private function configureClass(string $class): void
    {
        if (!class_exists($class)) {
            throw new HooksException("Listener class '$class' does not exist");
        }

        $reflection = new ReflectionClass($class);
        $file       = $reflection->getFileName() ?: $class;
        $realFile   = realpath($file) ?: $file;

        if (isset(self::$configuredFiles[$realFile])) {
            return;
        }
        self::$configuredFiles[$realFile] = true;

        if (!$reflection->isInstantiable()) {
            throw new HooksException("Listener class '$class' is not instantiable");
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            if (str_starts_with($methodName, '__')) {
                continue;
            }

            $priority = $this->getProperty([$class, $methodName]) ?? Priority::BASE;

            $this->add($methodName, $this->lazyListener($class, $methodName), $priority, [
                'file' => $reflection->getFileName() ?: '',
                'line' => $method->getStartLine() ?: '',
            ], [$class, $methodName]);
        }
    }

    /**
     * Builds a listener that instantiates its class - once, memoized - only when it first runs.
     *
     * @param class-string $class  Listener class discovered by configure().
     * @param string       $method Public method of that class to call.
     *
     * @return Closure
     */
    private function lazyListener(string $class, string $method): Closure
    {
        return function (mixed $value = null, mixed ...$values) use ($class, $method): mixed {
            $instance = self::$instances[$class] ??= new $class();

            return $instance->$method($value, ...$values);
        };
    }

    /**
     * Adds a hook for a given name.
     *
     * @param string                    $name     Name of hook.
     * @param string|array|callable     $function Listener to run: a function name, closure, or [object|class, method] array.
     * @param int                       $priority Hooks will be executed in order of priority in ascending order.
     * @param array{file: string, line: int|string}|null $source  Where to attribute this listener to, for
     *                                            {@see self::flushSource()} and `hooks:list` - defaults to
     *                                            the caller of add(), detected via a backtrace. Framework code
     *                                            that adds a listener on someone else's behalf (like configure()
     *                                            does for listener classes) should pass the real origin explicitly,
     *                                            since a backtrace would otherwise only ever point back at itself.
     * @param string|array|null         $identity What to compute the removal id from, when that differs from
     *                                            $function itself - e.g. configure() registers a closure that
     *                                            lazily instantiates its listener class, but wants it removable
     *                                            (and identified) the same cheap way a [class, method] pair
     *                                            would be, rather than reflecting that closure for an alias
     *                                            it can never have.
     *
     * @return void
     * @throws ReflectionException
     */
    public function add(
        string $name,
        string|array|callable $function,
        int $priority = Priority::BASE,
        ?array $source = null,
        string|array|null $identity = null
    ): void {
        $id = $this->makeId($name, $identity ?? $function);

        // #[HookListenerPriority] can only target methods, so only [object|class, method] callables need reflecting.
        $priority = is_array($function) ? ($this->getProperty($function) ?? $priority) : $priority;

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
     * Adds a hook that fires at most once: it removes itself the first time it runs.
     *
     * @param string   $name     Name of hook.
     * @param callable $function Listener to run once.
     * @param int      $priority Hooks will be executed in order of priority in ascending order.
     *
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

    /**
     * Checks if any hook exist for a given name.
     *
     * @param string $name Name of hook.
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset(self::$hooks[$name]);
    }

    /**
     * Return array of all hooks for all hooks, or of a given hook name.
     *
     * @param string|null $name Name of hook.
     *
     * @return array
     */
    public function get(?string $name = null): array
    {
        if (null !== $name) {
            return $this->sortedHooks($name);
        }

        $hooks = [];
        foreach (array_keys(self::$hooks) as $hookName) {
            $hooks[$hookName] = $this->sortedHooks($hookName);
        }

        return $hooks;
    }

    /**
     * Removes hook from a given hook, if existing.
     *
     * Note: an anonymous function can only be removed by passing its #[HookListenerAlias] string
     * (the same way it would be listed via {@see self::get()}), or the exact same callable/closure
     * reference it was added with - one added without an alias, and since discarded, has no stable
     * identity left to target and cannot be removed this way.
     *
     * @param string                     $name     Name of hook.
     * @param null|string|array|callable $function Hook to remove.
     *
     * @return bool Whether the hook existed.
     * @throws ReflectionException
     */
    public function flush(string $name, null|string|array|callable $function = null): bool
    {
        if (null === $function) {
            if (!isset(self::$hooks[$name])) {
                return false;
            }

            unset(self::$hooks[$name], self::$sorted[$name]);

            return true;
        }

        $id = $this->makeId($name, $function);
        if (!isset(self::$hooks[$name][$id])) {
            return false;
        }

        $this->removeListener($name, $id);

        return true;
    }

    /**
     * Unsets a single listener and, if that was the last one, the now-empty hook name entry too -
     * so has() never reports a hook as registered when it has no listeners left.
     *
     * @param string $name Name of hook.
     * @param string $id   Listener id, as returned by makeId().
     *
     * @return void
     */
    private function removeListener(string $name, string $id): void
    {
        unset(self::$hooks[$name][$id], self::$sorted[$name]);

        if (self::$hooks[$name] === []) {
            unset(self::$hooks[$name]);
        }
    }

    /**
     * Removes every listener attributed to the given file, or - if a directory is given - to any
     * file under it. Lets a plugin/theme drop everything it registered (e.g. when it gets
     * deactivated) without tracking each hook name and listener itself.
     *
     * A listener's attributed file is either the one that called add() (or, for a listener
     * discovered by configure(), the listener class's own file - see {@see self::add()}).
     *
     * Also un-marks every affected file as scanned, so a later configure() call over the same
     * path picks its listeners back up instead of skipping it as already configured.
     *
     * @param string $path Absolute path to a listener file or a plugin/theme directory.
     *
     * @return int Number of listeners removed.
     */
    public function flushSource(string $path): int
    {
        $real  = realpath($path) ?: $path;
        $isDir = is_dir($real);
        $removed = 0;

        foreach (self::$hooks as $name => $listeners) {
            foreach ($listeners as $id => $hook) {
                $file = $hook['source']['file'] ?? '';

                $matches = $isDir
                    ? str_starts_with($file, rtrim($real, '/\\') . DIRECTORY_SEPARATOR)
                    : $file === $real;

                if (!$matches) {
                    continue;
                }

                $this->removeListener($name, $id);
                unset(self::$configuredFiles[$file]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Filters value through queued hooks in order of priority.
     *
     * @param  string $name      Name of hook.
     * @param  mixed  $value     Original value to be filtered.
     * @param  mixed  ...$values Additional values for callback.
     * @return mixed             Filtered value.
     * @throws HooksException If the hook recurses more than {@see self::MAX_RECURSION_DEPTH} levels deep.
     */
    public function call(string $name, mixed $value = null, mixed ...$values): mixed
    {
        // Scoped to this method only: the try/finally below always brings a name's depth back to
        // 0 once its call() returns (normally or via exception), so there's never leftover state
        // for reset() to need to clear - unlike self::$hooks and friends, nothing outside call()
        // has a reason to read or reset it, so it doesn't need to be a class property.
        static $depthByName = [];

        self::$callCounts[$name] = (self::$callCounts[$name] ?? 0) + 1;

        if (!isset(self::$hooks[$name])) {
            return $value;
        }

        $depth = $depthByName[$name] ?? 0;

        if ($depth >= self::MAX_RECURSION_DEPTH) {
            throw new HooksException(
                "Hook '$name' recursed more than " . self::MAX_RECURSION_DEPTH . ' levels deep - '
                . 'a listener is likely re-triggering the same hook it is running on'
            );
        }

        $depthByName[$name] = $depth + 1;

        try {
            foreach ($this->sortedHooks($name) as $hook) {
                $value = ($hook['function'])($value, ...$values);
            }
        } finally {
            $depthByName[$name]--;
        }

        return $value;
    }

    /**
     * Number of times a hook has actually fired via call(), regardless of how many listeners it
     * has (or ever had). Useful in tests ("did this event actually happen?") and for diagnosing
     * a hook that never seems to run.
     *
     * @param string $name Name of hook.
     *
     * @return int
     */
    public function calls(string $name): int
    {
        return self::$callCounts[$name] ?? 0;
    }

    /**
     * Queues a hook to run after the response has been sent to the client (via
     * register_shutdown_function() and, under PHP-FPM, fastcgi_finish_request()) instead of
     * running inline. Meant for the side effects hooks are often used for - a notification, an
     * audit log entry - that the caller shouldn't have to wait for.
     *
     * Since it runs after the response is gone, its return value is discarded - use call() when
     * a listener needs to transform a value the caller depends on.
     *
     * @param string $name      Name of hook.
     * @param mixed  $value     Original value to pass to the deferred call.
     * @param mixed  ...$values Additional values for callback.
     *
     * @return void
     */
    public function defer(string $name, mixed $value = null, mixed ...$values): void
    {
        // Scoped to this method only: nothing outside defer() ever needs to read or reset this
        // flag, and register_shutdown_function() can't be un-registered anyway once it fires -
        // so, unlike self::$deferred itself, it doesn't need to be a class property.
        static $shutdownRegistered = false;

        self::$deferred[] = [$name, $value, $values];

        if ($shutdownRegistered) {
            return;
        }
        $shutdownRegistered = true;

        register_shutdown_function(function (): void {
            $this->runDeferred();
        });
    }

    /**
     * Runs and clears every hook queued by defer().
     *
     * @return void
     */
    private function runDeferred(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $queue           = self::$deferred;
        self::$deferred = [];

        foreach ($queue as [$name, $value, $values]) {
            $this->call($name, $value, ...$values);
        }
    }

    /**
     * Clears every registered hook and all configure()/once() bookkeeping.
     *
     * Hook state lives in static properties shared by the whole process, so tests that register
     * hooks would otherwise leak them into every test that runs after. Call this between tests
     * (or before re-scanning listeners from scratch) to start from a clean slate.
     *
     * call()'s recursion depth and defer()'s shutdown-registration flag are not listed here on
     * purpose - they live in method-local statics, not class properties, since nothing outside
     * those two methods ever needs to read or reset them.
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
    }
}
