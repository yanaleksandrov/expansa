<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;
use Expansa\View\Manager;
use Expansa\View\View as BaseView;

/**
 * Static access to the view manager: `View::create('form/checkbox', $data)->render()`.
 *
 * @method static void     configure(string|array $paths, string $cachePath = '')
 * @method static Manager  extend(string $extension, Closure $factory)
 * @method static BaseView create(string $view, array $data = [])
 * @method static bool     exists(string $view)
 * @method static void     share(string|array $key, mixed $value = null)
 * @method static Manager  addNamespace(string $namespace, string|array $paths, bool $prepend = false)
 * @method static void     startSection(string $name, ?string $content = null)
 * @method static void     extendSection(string $name, string $content)
 * @method static string   stopSection(bool $overwrite = false)
 * @method static string   yieldSection()
 * @method static string   yieldContent(string $name)
 */
class View extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return Manager::class;
    }
}
