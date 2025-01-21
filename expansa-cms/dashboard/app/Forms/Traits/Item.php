<?php

declare(strict_types=1);

namespace Dashboard\Forms\Traits;

trait Item
{
    public string $type        = '';

    public string $label       = '';

    public string $category    = '';

    public string $icon        = '';

    public string $description = '';

    public string $preview     = '';

    public string $view        = '';

    public array $defaults     = [];
}
