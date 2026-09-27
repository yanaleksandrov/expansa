<?php

declare(strict_types=1);

namespace Expansa\Lifecycle;

use Closure;
use Expansa\Lifecycle\Exceptions\AlreadyDeclared;
use Expansa\Lifecycle\Exceptions\AlreadyStarted;
use Throwable;

/**
 * Runs the application in a fixed order: phases one by one, then the first matching
 * context, then routing (not in the console). Every step fires hooks and is recorded
 * in the timeline; the terminate callback runs when the lifecycle starts.
 * Hooks and routing come from configure(), without it the steps run without them.
 *
 * @package Expansa\Lifecycle
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
     * Fires a hook by name: `before{Phase}`, `after{Phase}`, `enter{Context}`.
     */
    private ?Closure $hook = null;

    /**
     * Called once by run(), to schedule the work after the response.
     */
    private ?Closure $terminate = null;

    /**
     * Dispatches the routes registered by the context.
     */
    private ?Closure $route = null;

    /**
     * Returns the request URI the contexts are matched against.
     */
    private ?Closure $uriResolver = null;

    /**
     * Set the hooks and routing of the steps, replacing the previous ones.
     *
     * @param Closure|null $hook      `fn (string $name)`, fires a lifecycle hook.
     * @param Closure|null $terminate `fn ()`, called when run() starts, e.g. to defer the "terminate" hook.
     * @param Closure|null $route     `fn ()`, dispatches the routes after the context, not in the console.
     * @param Closure|null $uri       `fn (): string`, the request URI when run() gets none.
     * @return void
     */
    public function configure(?Closure $hook = null, ?Closure $terminate = null, ?Closure $route = null, ?Closure $uri = null): void
    {
        $this->hook        = $hook;
        $this->terminate   = $terminate;
        $this->route       = $route;
        $this->uriResolver = $uri;
    }

    /**
     * Declare a phase. Phases run in declaration order, wrapped by "before{Name}" and "after{Name}" hooks.
     * $when is a ready bool or a callable checked at run time; a skipped phase fires no hooks.
     *
     * @param string        $name
     * @param bool|callable $when
     * @param callable      $callback
     * @return static
     * @throws AlreadyStarted|AlreadyDeclared
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
     * @param string        $name
     * @param bool|callable $when
     * @param callable      $callback
     * @return static
     * @throws AlreadyStarted|AlreadyDeclared
     */
    public function context(string $name, bool|callable $when, callable $callback): static
    {
        $this->guard($name, $this->contexts, 'context');

        $this->contexts[$name] = ['when' => $when, 'callback' => $callback];

        return $this;
    }

    /**
     * Run the lifecycle once. $uri defaults to the configured URI source, in the console to an empty string.
     * $catch gets anything a step throws and stops the remaining steps; without it the exception propagates.
     *
     * @param string|null                    $uri
     * @param callable(Throwable): void|null $catch
     * @return void
     * @throws AlreadyStarted
     */
    public function run(?string $uri = null, ?callable $catch = null): void
    {
        if ($this->started) {
            throw new AlreadyStarted('Lifecycle has already been run');
        }
        $this->started = true;
        $this->uri     = $uri ?? (PHP_SAPI === 'cli' ? '' : null);

        // before the steps: a step may exit early (redirect, exit)
        if ($this->terminate !== null) {
            ($this->terminate)();
        }

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
     * Get the name of the matched context; null before run() or when none matched.
     *
     * @return string|null
     */
    public function current(): ?string
    {
        $this->resolve();

        return $this->current;
    }

    /**
     * Check if a context is the matched one.
     *
     * @param string $context
     * @return bool
     */
    public function is(string $context): bool
    {
        return $this->current() === $context;
    }

    /**
     * Get the executed steps with duration in milliseconds and memory growth in bytes.
     *
     * @return array<int, array{name: string, type: string, time: float, memory: int}>
     */
    public function timeline(): array
    {
        return $this->timeline;
    }

    private function steps(): void
    {
        $hook = $this->hook ?? static fn (string $name) => null;

        foreach ($this->phases as $name => $phase) {
            if (! $this->passes($phase['when'])) {
                continue;
            }

            $this->measure($name, 'phase', function () use ($hook, $name, $phase) {
                $hook('before' . ucfirst($name));
                ($phase['callback'])();
                $hook('after' . ucfirst($name));
            });
        }

        $this->resolve();

        if ($this->current !== null) {
            $name    = $this->current;
            $context = $this->contexts[$name];

            $this->measure($name, 'context', function () use ($hook, $name, $context) {
                ($context['callback'])();
                $hook('enter' . ucfirst($name));
            });
        }

        // dispatches the routes registered by the context; the console has none
        if ($this->route !== null && PHP_SAPI !== 'cli') {
            $this->measure('route', 'route', $this->route);
        }
    }

    /**
     * @throws AlreadyStarted|AlreadyDeclared
     */
    private function guard(string $name, array $declared, string $type): void
    {
        if ($this->started) {
            throw new AlreadyStarted("Cannot declare $type after the lifecycle has started");
        }

        if (isset($declared[$name])) {
            throw new AlreadyDeclared("The $type '$name' is already declared");
        }
    }

    private function resolve(): void
    {
        // before run() not all contexts may be declared yet, so nothing is memoized
        if (! $this->started || $this->resolved) {
            return;
        }
        $this->resolved = true;

        if (! $this->contexts) {
            return;
        }

        $uri = $this->uri ?? ($this->uriResolver !== null ? ($this->uriResolver)() : '');

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
