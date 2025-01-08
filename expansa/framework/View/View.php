<?php

declare(strict_types=1);

namespace Expansa\View;

use Expansa\Support\Str;
use Expansa\Support\Traits\Macroable;
use Expansa\View\Engines\Engine;
use Expansa\View\Exception\ViewException;

class View
{
    use Macroable {
        __call as macroCall;
    }

    public function __construct(
        protected Factory $factory,
        protected Engine $engine,
        protected string $name,
        protected string $path,
        protected array $data
    ) {} // phpcs:ignore

    public function render(): string
    {
        return $this->engine->get($this->path, $this->data);
    }

    public function compiled()
    {
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
        return $this->name;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toHtml(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function __call(string $method, array $parameters)
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
