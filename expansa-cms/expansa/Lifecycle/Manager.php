<?php

declare(strict_types=1);

namespace Expansa\Lifecycle;

use Expansa\Facades\Hook;
use Expansa\Facades\Route;
use Expansa\Lifecycle\Exception\LifecycleException;
use Throwable;

/**
 * Runs the application in a fixed order: phases one by one, then the first matching
 * context, then routing (not in the console). Every step fires hooks and is recorded
 * in the timeline; the "terminate" hook runs after the response is sent.
 */
final class Manager
{
    /**
     * @var array<string, array{callback: callable, when: bool|callable}>
     */
    private array $phases = [];

    /**
     * @var array<string, array{when: bool|callable, callback: callable}>
     */
    private array $contexts = [];

    private ?string $current = null;

    private ?string $uri = null;

    private bool $resolved = false;

    private bool $started = false;

    /**
     * @var array<int, array{name: string, type: string, time: float, memory: int}>
     */
    private array $timeline = [];

    /**
     * Declare a phase. Phases run in declaration order, wrapped by "before{Name}" and "after{Name}" hooks.
     * $when is a ready bool or a callable checked at run time; a skipped phase fires no hooks.
     *
     * @throws LifecycleException
     */
    public function phase(string $name, bool|callable $when, callable $callback): static
    {
        $this->guard($name, $this->phases, 'phase');

        $this->phases[$name] = ['callback' => $callback, 'when' => $when];

        return $this;
    }

    /**
     * Declare a context. After the phases only the first match runs, followed by the "enter{Name}" hook.
     * $when is a ready bool or $when(string $uri); matched on the first current()/is() call, even from a phase.
     *
     * @throws LifecycleException
     */
    public function context(string $name, bool|callable $when, callable $callback): static
    {
        $this->guard($name, $this->contexts, 'context');

        $this->contexts[$name] = ['when' => $when, 'callback' => $callback];

        return $this;
    }

    /**
     * Run the lifecycle once. $uri defaults to the router's current URI, in the console to an empty string.
     * $catch gets anything a step throws and stops the remaining steps; without it the exception propagates.
     *
     * @param callable(Throwable): void|null $catch
     * @throws LifecycleException
     */
    public function run(?string $uri = null, ?callable $catch = null): void
    {
        if ($this->started) {
            throw new LifecycleException('Lifecycle has already been run');
        }
        $this->started = true;
        $this->uri     = $uri ?? (PHP_SAPI === 'cli' ? '' : null);

        // after the response is sent, also when a step exits early (redirect, exit)
        Hook::defer('terminate');

        try {
            $this->steps();
        } catch (Throwable $e) {
            if ($catch === null) {
                throw $e;
            }

            $catch($e);
        }
    }

    /**
     * Name of the matched context; null before run() or when none matched.
     */
    public function current(): ?string
    {
        $this->resolve();

        return $this->current;
    }

    public function is(string $context): bool
    {
        return $this->current() === $context;
    }

    /**
     * Executed steps with duration in milliseconds and memory growth in bytes.
     *
     * @return array<int, array{name: string, type: string, time: float, memory: int}>
     */
    public function timeline(): array
    {
        return $this->timeline;
    }

    private function steps(): void
    {
        foreach ($this->phases as $name => $phase) {
            if (!$this->passes($phase['when'])) {
                continue;
            }

            $hook = ucfirst($name);

            $this->measure($name, 'phase', function () use ($hook, $phase) {
                Hook::call("before$hook");
                ($phase['callback'])();
                Hook::call("after$hook");
            });
        }

        $this->resolve();

        if ($this->current !== null) {
            $name    = $this->current;
            $context = $this->contexts[$name];

            $this->measure($name, 'context', function () use ($name, $context) {
                ($context['callback'])();
                Hook::call('enter' . ucfirst($name));
            });
        }

        // dispatches the routes registered by the context; the console has none
        if (PHP_SAPI !== 'cli') {
            $this->measure('route', 'route', fn () => Route::run());
        }
    }

    /**
     * @throws LifecycleException
     */
    private function guard(?string $name, array $declared, string $type): void
    {
        if ($this->started) {
            throw new LifecycleException("Cannot declare $type after the lifecycle has started");
        }

        if ($name !== null && isset($declared[$name])) {
            throw new LifecycleException("The $type '$name' is already declared");
        }
    }

    private function resolve(): void
    {
        // before run() not all contexts may be declared yet, so nothing is memoized
        if (!$this->started || $this->resolved) {
            return;
        }
        $this->resolved = true;

        if (!$this->contexts) {
            return;
        }

        $uri = $this->uri ?? Route::uri();

        foreach ($this->contexts as $name => $context) {
            if ($this->passes($context['when'], $uri)) {
                $this->current = $name;
                break;
            }
        }
    }

    private function passes(bool|callable $when, mixed ...$args): bool
    {
        return is_bool($when) ? $when : (bool) $when(...$args);
    }

    private function measure(string $name, string $type, callable $callback): void
    {
        $time   = microtime(true);
        $memory = memory_get_usage();

        // record even when the step exits early (redirect, exit) or throws
        try {
            $callback();
        } finally {
            $this->timeline[] = [
                'name'   => $name,
                'type'   => $type,
                'time'   => round((microtime(true) - $time) * 1000, 3),
                'memory' => memory_get_usage() - $memory,
            ];
        }
    }
}
