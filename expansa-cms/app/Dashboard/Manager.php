<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Models\Option;
use App\Models\User;
use Closure;
use Expansa\Access\Exceptions\AccessDenied;
use Expansa\Builders\Tree;
use Expansa\Facades\Access;
use Expansa\Facades\Asset;
use Expansa\Facades\View;
use Expansa\Http\Exceptions\NotFound;
use Expansa\Http\Request;

/**
 * Pages of the dashboard by slug: a slug opens the template `screens/{slug}`, and the data providers
 * registered for it fill the template. The core registers its pages the same way as plugins:
 *
 * ```php
 * Dashboard::page('orders', can: 'orders_read', title: t('Orders'));
 * Dashboard::data('orders', fn (Request $request) => ['orders' => Order::latest()]);
 * ```
 *
 * A page without registration still opens when its template exists. Providers run in the order of
 * priority, then of registration, and get the data collected before them; `*` runs on every page.
 * A provider throws NotFound or AccessDenied to show the 404 or 403 page instead.
 *
 * @package App\Dashboard
 */
final class Manager
{
    /**
     * Options of the registered pages by slug.
     *
     * @var array<string, array{view?: string|Closure(Request): string, can?: string, title?: string|Closure(array<string, mixed>): string}>
     */
    private array $pages = [];

    /**
     * Data providers by slug and priority.
     *
     * @var array<string, array<int, list<callable(Request, array<string, mixed>): array<string, mixed>>>>
     */
    private array $providers = [];

    /**
     * Register a page or change the options of a registered one; the options left out keep their values.
     *
     * @param string                                         $slug  Path after the dashboard slug: `site-health`.
     * @param string|Closure(Request): string|null           $view  Template name or absolute path, `screens/{slug}` by default.
     * @param string|null                                    $can   Permission to open the page.
     * @param string|Closure(array<string, mixed>): string|null $title Title, a closure gets the page data; the menu item label by default.
     * @return void
     */
    public function page(string $slug, string|Closure|null $view = null, ?string $can = null, string|Closure|null $title = null): void
    {
        $this->pages[$slug] = array_filter(['view' => $view, 'can' => $can, 'title' => $title], fn ($option) => $option !== null)
            + ($this->pages[$slug] ?? []);
    }

    /**
     * Add a data provider of a page: it returns variables of the template, merged into the data.
     *
     * @param string                                                         $slug     Page slug, `*` for every page.
     * @param callable(Request, array<string, mixed>): array<string, mixed> $provider Gets the request and the data collected so far.
     * @param int                                                            $priority Lower runs earlier.
     * @return void
     */
    public function data(string $slug, callable $provider, int $priority = 10): void
    {
        $this->providers[$slug][$priority][] = $provider;
    }

    /**
     * Render a page in the layout: checks the permission, collects the data, shows 403 or 404 when needed.
     *
     * @param string  $slug
     * @param Request $request
     * @param string  $layout Template wrapping the page: `welcome`, or `guest` for the sign-in pages.
     * @return string
     */
    public function render(string $slug, Request $request, string $layout = 'welcome'): string
    {
        return $this->show($slug, $request, $layout, []);
    }

    /**
     * Render a page or, when it is closed or missing, the 403 or 404 page with the reason as `message`.
     *
     * @param string               $slug
     * @param Request              $request
     * @param string               $layout
     * @param array<string, mixed> $data    Data before the providers.
     * @return string
     */
    private function show(string $slug, Request $request, string $layout, array $data): string
    {
        $page = $this->pages[$slug] ?? [];
        $view = $page['view'] ?? "screens/$slug";
        $view = $view instanceof Closure ? $view($request) : $view;

        try {
            if (! $this->allows($slug, $page, $request)) {
                throw new AccessDenied($page['can'] ?? $slug);
            }

            if (! View::exists($view)) {
                throw new NotFound();
            }

            $data  = $this->collect($slug, $request, $data);
            $title = $this->title($slug, $page, $data, $request);
        } catch (AccessDenied | NotFound $e) {
            $error = $e instanceof NotFound ? '404' : '403';
            if ($slug === $error) {
                throw $e;
            }

            return $this->show($error, $request, $layout, ['message' => $e instanceof NotFound ? $e->getMessage() : '']);
        }

        if (in_array($slug, ['403', '404'], true)) {
            http_response_code((int) $slug);
        }

        // the layout keys win: `page` is the template the layout includes
        $content = view($layout, [...$data, 'page' => $view, 'title' => $title]);

        // co-located CSS/JS of the page template, see Assets\Manager::discover()
        Asset::discover($content->path);

        return $content->beautify()->render();
    }

    /**
     * Whether the current user may open the page: its permission, and the one of the menu item linking to it.
     *
     * @param string               $slug
     * @param array<string, mixed> $page
     * @param Request              $request
     * @return bool
     */
    private function allows(string $slug, array $page, Request $request): bool
    {
        if (isset($page['can']) && ! Access::allows(User::current(), $page['can'])) {
            return false;
        }

        return Tree::allowsUrl($slug, $request->query);
    }

    /**
     * Run the providers of the page and of every page by priority.
     *
     * @param string               $slug
     * @param Request              $request
     * @param array<string, mixed> $data    Data before the providers.
     * @return array<string, mixed>
     */
    private function collect(string $slug, Request $request, array $data): array
    {
        $queue = $this->providers['*'] ?? [];
        foreach ($this->providers[$slug] ?? [] as $priority => $providers) {
            $queue[$priority] = [...$queue[$priority] ?? [], ...$providers];
        }
        ksort($queue);

        foreach ($queue as $providers) {
            foreach ($providers as $provider) {
                $data = [...$data, ...$provider($request, $data)];
            }
        }

        return $data;
    }

    /**
     * Document title: "{page} — {site name}". The page name is the registered title, or the label
     * of the dashboard menu item linking to the current URL, e.g. "Custom Fields" for "field-groups".
     *
     * @param string               $slug
     * @param array<string, mixed> $page
     * @param array<string, mixed> $data
     * @param Request              $request
     * @return string
     */
    private function title(string $slug, array $page, array $data, Request $request): string
    {
        $site  = (string) Option::get('site.name', '');
        $site  = $site !== '' ? $site : 'Expansa';
        $table = $request->query['table'] ?? null;

        $name = $page['title'] ?? null;
        $name = match (true) {
            $name instanceof Closure => $name($data),
            $name !== null           => $name,
            default                  => $this->menuTitle(is_string($table) ? "$slug?table=$table" : $slug)
                ?? $this->menuTitle(is_string($table) ? $table : $slug)
                ?? ucfirst(str_replace('-', ' ', $slug)),
        };

        return $name === '' ? $site : "$name — $site";
    }

    /**
     * Label of the dashboard menu item with the URL.
     *
     * @param string $url
     * @return string|null
     */
    private function menuTitle(string $url): ?string
    {
        foreach (['dashboard-main-menu', 'dashboard-panel-menu', 'dashboard-user-menu'] as $menu) {
            foreach (Tree::get($menu)->items as $item) {
                if (($item['url'] ?? null) === $url && is_string($item['title'] ?? null) && $item['title'] !== '') {
                    return $item['title'];
                }
            }
        }

        return null;
    }
}
