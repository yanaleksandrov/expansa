<?php

declare(strict_types=1);

namespace Expansa\Assets\Abstracts;

use Expansa\Assets\Support\AssetHandler;

abstract class Provider
{
    use AssetHandler;

    /**
     * Plug & render an asset, typically used for including the asset in the final output.
     */
    abstract public function render(Provider $asset): string;

    /**
     * Minifies the provided code, typically used to reduce the size of CSS or JavaScript.
     */
    abstract public function minify(string $code): string;
}
