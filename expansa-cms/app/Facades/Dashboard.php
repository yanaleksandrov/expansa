<?php

declare(strict_types=1);

namespace App\Facades;

use Closure;
use Expansa\Http\Request;
use Expansa\Patterns\Facade;

/**
 * Dashboard pages facade: register pages and their data providers by slug.
 *
 * @method static void page(string $slug, string|Closure|null $view = null, ?string $can = null, string|Closure|null $title = null)
 * @method static void data(string $slug, callable $provider, int $priority = 10)
 * @method static string render(string $slug, Request $request, string $layout = 'welcome')
 *
 * @see \App\Dashboard\Manager
 */
class Dashboard extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \App\Dashboard\Manager::class;
    }
}
