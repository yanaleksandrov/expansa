<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;
use Expansa\View\Engines\EngineManager;
use Expansa\View\Finder;
use Expansa\View\View as BaseView;

/**
 * The View class provides a static interface to the view factory, allowing the creation and management of views.
 *
 * @method static BaseView make(string $view, array $data = [])
 */
class View extends Facade
{
    private static string $views = '';

    /**
     * @var array<string, mixed>
     */
    private static array $options = [];

    /**
     * Set the views directory before the first view is made; with $cachePath compiled templates are cached there.
     */
    public static function configure(string $viewsPath, string $cachePath = ''): void
    {
        self::$views   = $viewsPath;
        self::$options = $cachePath !== '' ? ['cache' => true, 'cache_path' => $cachePath] : [];
    }

    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\View\Factory::class;
    }

    protected static function getConstructorArgs(): array
    {
        return [
            new Finder(self::$views),
            new EngineManager(),
            self::$options,
        ];
    }
}
