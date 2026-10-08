<?php

declare(strict_types=1);

namespace Expansa\View;

use Closure;
use Expansa\View\Contracts\Engine;
use Expansa\View\Engines\Blade;
use Expansa\View\Engines\File;
use Expansa\View\Engines\Js;
use Expansa\View\Engines\Php;
use Expansa\View\Exceptions\ViewNotFound;
use Expansa\View\Internal\Compiler;
use Expansa\View\Internal\Finder;
use InvalidArgumentException;
use LogicException;

/**
 * Creates views from the configured directories, the View facade instance. The engine is picked by
 * the template extension and created once. Templates see the shared data and the manager as `$__env`,
 * Blade sections live here.
 *
 * @package Expansa\View
 */
final class Manager
{
    /**
     * Built-in template extensions, a view with several of them gets the first one.
     */
    private const array EXTENSIONS = ['blade.php', 'php', 'html', 'css', 'js'];

    /**
     * Variables of every view.
     *
     * @var array<string, mixed>
     */
    public private(set) array $shared = [];

    /**
     * @var Finder
     */
    private Finder $finder;

    /**
     * Directories of views without a namespace.
     *
     * @var string[]
     */
    private array $paths = [];

    /**
     * @var string
     */
    private string $cachePath = '';

    /**
     * Created engines by extension.
     *
     * @var array<string, Engine>
     */
    private array $engines = [];

    /**
     * Custom engine factories by extension.
     *
     * @var array<string, Closure>
     */
    private array $factories = [];

    /**
     * Section contents by name.
     *
     * @var array<string, string>
     */
    private array $sections = [];

    /**
     * Names of the started sections.
     *
     * @var string[]
     */
    private array $sectionStack = [];

    public function __construct()
    {
        $this->finder          = $this->createFinder();
        $this->shared['__env'] = $this;
    }

    /**
     * Set the view directories; created engines and found views are dropped.
     *
     * @param string|string[] $paths     Directories of views without a namespace.
     * @param string          $cachePath Directory of compiled Blade templates, without it they are compiled on
     *                                   the first render in each request.
     * @return void
     */
    public function configure(string|array $paths, string $cachePath = ''): void
    {
        $this->paths     = (array) $paths;
        $this->cachePath = $cachePath;
        $this->finder    = $this->createFinder();
        $this->engines   = [];
    }

    /**
     * Add an engine for an extension or replace a built-in one.
     *
     * @param string  $extension Extension without the dot: `twig.php`.
     * @param Closure $factory   Returns an Engine.
     * @return static
     */
    public function extend(string $extension, Closure $factory): static
    {
        $this->factories[$extension] = $factory;

        unset($this->engines[$extension]);
        $this->finder->addExtension($extension);

        return $this;
    }

    /**
     * Create a view: a name in the view directories, `namespace::name` or an absolute path without extension.
     *
     * @param string               $view
     * @param array<string, mixed> $data Variables over the shared ones.
     * @return View
     * @throws ViewNotFound
     */
    public function create(string $view, array $data = []): View
    {
        $file = $this->finder->find($view) ?? throw new ViewNotFound("View [$view] not found.");

        return new View(
            $this->engines[$file['extension']] ??= $this->resolve($file['extension']),
            $view,
            $file['path'],
            $data + $this->shared,
        );
    }

    /**
     * Get the engine of an extension, created on the first call.
     *
     * @param string $extension Template extension: `blade.php`, `php`, `html`, `css`, `js` or a custom one.
     * @return Engine
     */
    public function getEngine(string $extension): Engine
    {
        return $this->engines[$extension] ??= $this->resolve($extension);
    }

    /**
     * Get a shared variable.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getShared(string $key, mixed $default = null): mixed
    {
        return $this->shared[$key] ?? $default;
    }

    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    /**
     * Share a variable with every view, or several with an array.
     *
     * @param string|array<string, mixed> $key
     * @param mixed                       $value
     * @return void
     */
    public function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = [...$this->shared, ...$key];
        } else {
            $this->shared[$key] = $value;
        }
    }

    /**
     * Add directories of views for `namespace::view` names.
     *
     * @param string          $namespace
     * @param string|string[] $paths
     * @param bool            $prepend Search them before the current directories of the namespace.
     * @return static
     */
    public function addNamespace(string $namespace, string|array $paths, bool $prepend = false): static
    {
        $this->finder->addNamespace($namespace, $paths, $prepend);

        return $this;
    }

    /**
     * Start a section: capture the output until stopSection(), or set its content at once.
     *
     * @param string      $name
     * @param string|null $content
     * @return void
     */
    public function startSection(string $name, ?string $content = null): void
    {
        if ($content === null) {
            ob_start();
            $this->sectionStack[] = $name;
        } else {
            $this->extendSection($name, $content);
        }
    }

    /**
     * Set the section content, `@parent` in the current content is replaced with it.
     *
     * @param string $name
     * @param string $content
     * @return void
     */
    public function extendSection(string $name, string $content): void
    {
        if (isset($this->sections[$name])) {
            $content = str_replace('@parent', $content, $this->sections[$name]);
        }

        $this->sections[$name] = $content;
    }

    /**
     * Stop the last started section and store its output.
     *
     * @param bool $overwrite Replace the content instead of extending it.
     * @return string Section name.
     * @throws LogicException If no section is started.
     */
    public function stopSection(bool $overwrite = false): string
    {
        if ($this->sectionStack === []) {
            throw new LogicException('Cannot end a section without first starting one.');
        }

        $name = array_pop($this->sectionStack);

        if ($overwrite) {
            $this->sections[$name] = ob_get_clean();
        } else {
            $this->extendSection($name, ob_get_clean());
        }

        return $name;
    }

    /**
     * Stop the last started section and get its content.
     *
     * @return string
     */
    public function yieldSection(): string
    {
        return $this->sectionStack === [] ? '' : $this->yieldContent($this->stopSection());
    }

    public function yieldContent(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    /**
     * Create the engine of an extension.
     *
     * @param string $extension
     * @return Engine
     */
    private function resolve(string $extension): Engine
    {
        if (isset($this->factories[$extension])) {
            return ($this->factories[$extension])();
        }

        return match ($extension) {
            'blade.php'   => new Blade(new Compiler($this->cachePath, cache: $this->cachePath !== '')),
            'php'         => new Php(),
            'html', 'css' => new File(),
            'js'          => new Js(),
            default       => throw new InvalidArgumentException("No engine for the [$extension] extension."),
        };
    }

    private function createFinder(): Finder
    {
        return new Finder($this->paths, [...array_diff(array_keys($this->factories), self::EXTENSIONS), ...self::EXTENSIONS]);
    }
}
