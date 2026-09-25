<?php

declare(strict_types=1);

namespace Expansa\Lifecycle;

use Expansa\Facades\Hook;
use Expansa\Facades\Route;
use Expansa\Lifecycle\Exception\LifecycleException;
use Throwable;

/**
 * Runs the application in a fixed order: phases one by one, then the first matching
 * context, then the fallback. Every step fires hooks and is recorded in the timeline;
 * the "terminate" hook runs after the response is sent.
 */
final class Manager
{
    /**
     * @var array<string, array{callback: callable, when: callable|null}>
     */
    private array $phases = [];

    /**
     * @var array<string, array{when: callable, callback: callable}>
     */
    private array $contexts = [];

    /**
     * @var callable|null
     */
    private $fallback = null;

    /**
     * @var callable|null
     */
    private $catch = null;

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
     * A phase with $when runs only if it returns true; a skipped phase fires no hooks.
     *
     * @throws LifecycleException
     */
    public function phase(string $name, callable $callback, ?callable $when = null): static
    {
        $this->guard($name, $this->phases, 'phase');

        $this->phases[$name] = ['callback' => $callback, 'when' => $when];

        return $this;
    }

    /**
     * Declare a context. Only the first one whose $when(string $uri) returns true runs, after the phases,
     * followed by the "enter{Name}" hook. It is matched on the first current()/is() call, even from a phase.
     *
     * @throws LifecycleException
     */
    public function context(string $name, callable $when, callable $callback): static
    {
        $this->guard($name, $this->contexts, 'context');

        $this->contexts[$name] = ['when' => $when, 'callback' => $callback];

        return $this;
    }

    /**
     * Declare the step that runs after the context, e.g. routing.
     *
     * @throws LifecycleException
     */
    public function fallback(callable $callback): static
    {
        $this->guard(null, [], 'fallback');

        $this->fallback = $callback;

        return $this;
    }

    /**
     * Declare the handler for anything a step throws; without it the exception propagates.
     *
     * @param callable(Throwable): void $handler
     * @throws LifecycleException
     */
    public function catch(callable $handler): static
    {
        $this->guard(null, [], 'catch');

        $this->catch = $handler;

        return $this;
    }

    /**
     * Run the lifecycle once. $uri defaults to the router's current URI, in the console to an empty string.
     *
     * @throws LifecycleException
     */
    public function run(?string $uri = null): void
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
            if ($this->catch === null) {
                throw $e;
            }

            ($this->catch)($e);
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
            if ($phase['when'] !== null && !($phase['when'])()) {
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

        if ($this->fallback !== null) {
            $this->measure('fallback', 'fallback', $this->fallback);
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

        $uri = $this->uri ?? Route::getCurrentUri();

        foreach ($this->contexts as $name => $context) {
            if (($context['when'])($uri)) {
                $this->current = $name;
                break;
            }
        }
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
