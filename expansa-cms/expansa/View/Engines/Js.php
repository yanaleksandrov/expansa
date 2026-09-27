<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

use Expansa\View\Contracts\Engine;

/**
 * Returns a JavaScript file wrapped in a script tag.
 *
 * @package Expansa\View
 */
final class Js implements Engine
{
    public private(set) string $lastRendered = '';

    public function render(string $path, array $data = []): string
    {
        $this->lastRendered = $path;

        return '<script>' . file_get_contents($path) . '</script>';
    }
}
