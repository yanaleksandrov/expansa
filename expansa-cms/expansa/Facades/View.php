<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;
use Expansa\View\View as BaseView;

/**
 * The View class provides a static interface to the view factory, allowing the creation and management of views.
 *
 * @method static BaseView make(string $view, array $data = [])
 */
class View extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\View\Factory';
    }

    protected static function getConstructorArgs(): array
    {
        return [
            new \Expansa\View\Finder(EX_PATH . 'dashboard/views'),
            new \Expansa\View\Engines\EngineManager(),
            [
                'paths'      => [
                    root('dashboard'),
                ],
                'cache_path' => root('cache/views'),
                'css_path'   => root('public/css'),
                'js_path'    => root('public/js'),
                'cache'      => true,
            ],
        ];
    }
}
