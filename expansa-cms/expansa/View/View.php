<?php

declare(strict_types=1);

namespace Expansa\View;

use BadMethodCallException;
use Expansa\Support\Str;
use Expansa\Support\Traits\Macroable;
use Expansa\View\Contracts\Engine;
use Expansa\View\Internal\Html;

/**
 * A template with its variables, rendered on render() or string conversion.
 * `withTitle('x')` is `with('title', 'x')`.
 *
 * @package Expansa\View
 */
final class View
{
    use Macroable {
        __call as macroCall;
    }

    private bool $shouldBeautify = false;

    /**
     * Options of the HTML beautifier: indent_size, indent_char, unformatted and others.
     */
    private array $beautifyOptions = [];

    private bool $shouldMinify = false;

    public function __construct(

        /**
         * Engine chosen by the file extension.
         */
        private readonly Engine $engine,

        /**
         * View name as requested, e.g. "namespace::view".
         */
        public readonly string $name,

        /**
         * Path to the resolved template file.
         */
        public readonly string $path,

        /**
         * Template variables, merged over the manager's shared data.
         *
         * @var array<string, mixed>
         */
        public private(set) array $data,
    ) {}

    public function render(): string
    {
        $content = $this->engine->render($this->path, $this->data);

        if ($this->shouldBeautify) {
            $content = new Html($this->beautifyOptions)->beautify($content);
        } elseif ($this->shouldMinify) {
            $content = new Html()->minify($content);
        }

        return $content;
    }

    /**
     * Indent the rendered HTML; call it on the whole page, not on each partial, which can't know
     * its nesting depth. Cancels minify().
     *
     * @param array $options Options of the beautifier: indent_size, indent_char, unformatted and others.
     * @return static
     */
    public function beautify(array $options = []): static
    {
        $this->shouldBeautify  = true;
        $this->shouldMinify    = false;
        $this->beautifyOptions = $options;

        return $this;
    }

    /**
     * Collapse the rendered HTML to one line without comments; call it once on the whole page.
     * Cancels beautify().
     *
     * @return static
     */
    public function minify(): static
    {
        $this->shouldMinify   = true;
        $this->shouldBeautify = false;

        return $this;
    }

    /**
     * Add variables: a name and a value or an array of them.
     *
     * @param string|array<string, mixed> $key
     * @param mixed                       $value
     * @return static
     */
    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = [...$this->data, ...$key];
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        if (str_starts_with($method, 'with')) {
            return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
        }

        throw new BadMethodCallException(sprintf('Method %s::%s does not exist', static::class, $method));
    }
}
