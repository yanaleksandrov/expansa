<?php

declare(strict_types=1);

namespace Expansa\View\Engines;

class JsEngine extends FileEngine
{
    #[\Override]
    public function get(string $path, array $data = []): string
    {
        return '<script>' . parent::get($path) . '</script>';
    }
}