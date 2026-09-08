<?php

declare(strict_types=1);

namespace Expansa\View;

use Expansa\Support\Str;
use Expansa\Support\Traits\Macroable;
use Expansa\View\Engines\Engine;
use Expansa\View\Exception\ViewException;
use Expansa\View\Support\Html;

class View
{
    use Macroable {
        __call as macroCall;
    }

    protected bool $shouldBeautify = false;

    protected array $beautifyOptions = [];

    protected bool $shouldMinify = false;

    public function __construct(
        protected readonly Factory $factory,
        protected readonly Engine $engine,
        protected readonly string $name,
        protected readonly string $path,
        protected array $data
    ) {} // phpcs:ignore

    public function render(): string
    {
        $content = $this->engine->get($this->path, $this->data);

        if ($this->shouldBeautify) {
            $content = new Html($this->beautifyOptions)->beautify($content);
        } elseif ($this->shouldMinify) {
            $content = new Html()->minify($content);
        }

        return $content;
    }

    /**
     * Pretty-print this view's rendered HTML output before returning it from
     * render(). Opt-in and off by default - call e.g. once on the outermost
     * view of a fully assembled page (see app/Controllers/Web.php), not on
     * every individual sub-view/field partial: beautifying a fragment on its
     * own can't know the indentation depth it will end up nested at once
     * concatenated into its parent, so doing it per-fragment produces flatter,
     * wrong-looking indentation once everything is assembled - beautify the
     * final page as a whole instead.
     *
     * @param array $options Passed straight through to Html's constructor
     *                        (indent_size, indent_char, unformatted, ...).
     */
    public function beautify(array $options = []): static
    {
        $this->shouldBeautify  = true;
        $this->shouldMinify    = false;
        $this->beautifyOptions = $options;

        return $this;
    }

    /**
     * Strip this view's rendered HTML output down to a single compact line
     * (collapsed whitespace, comments removed) before returning it from
     * render(). Opt-in and off by default; mutually exclusive with beautify()
     * on the same view - whichever of the two is called last wins, since
     * doing both would just mean throwing away the formatting pass right
     * after paying for it. Same "call it on the final assembled page, not on
     * every sub-view" reasoning as beautify(): minifying a fragment on its
     * own is harmless (it doesn't depend on nesting depth the way indentation
     * does), but there's rarely a reason to pay for it more than once per page.
     */
    public function minify(): static
    {
        $this->shouldMinify   = true;
        $this->shouldBeautify = false;

        return $this;
    }

    public function with(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getData(): array
    {
        return $this->data;
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

        throw new ViewException(sprintf('Method %s::%s does not exist', static::class, $method));
    }
}
