<?php

declare(strict_types=1);

namespace App\Controllers;

use App;
use App\Models\Options;
use App\Models\Slug;
use App\Models\User;
use Expansa\Builders\Tree;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Facades\Lifecycle;

final class Web
{
    /**
     * Installer page, routed by the "install" lifecycle context: every slug but "install" redirects to it.
     */
    public function install($slug): void
    {
        if ($slug !== 'install') {
            redirect('install');
        }

        $welcome = view('welcome', ['slug' => 'install', 'title' => t('Install Expansa')]);

        Asset::discover($welcome->getPath());

        echo $welcome->beautify()->render();
    }

    /**
     * Pages of the "auth", "dashboard" and "web" lifecycle contexts; access checks and assets are done by the context.
     */
    public function index($slug): void
    {
        if (Lifecycle::is('dashboard')) {
            /**
             * Expansa dashboard panel.
             *
             * @param string $slug Dashboard page root slug.
             */
            $dashboard = Hook::call('dashboardRootSlug', 'dashboard');

            $slug = str_replace('dashboard/', '', $slug);

            // land on the chat page by default (bare "dashboard" or "dashboard/" slug).
            if ($slug === '' || $slug === $dashboard) {
                $slug = 'chat';
            }
        }

        // not allow some slugs for logged user, they are reserved (e.g. "dashboard/sign-in" or "install").
        $blackListSlugs = ['install', 'sign-in', 'sign-up', 'reset-password'];
        if (in_array($slug, $blackListSlugs, true) && User::isLogged()) {
            redirect('dashboard');
        }

        $title = $this->title($slug);

        if (Lifecycle::is('auth')) {
            $page = 'welcome';
        }

        // dashboard views are not public pages: outside the dashboard only the front page and the post-install page exist
        if (Lifecycle::is('web') && $slug !== '' && !($slug === 'installed' && User::isLogged())) {
            http_response_code(404);

            $page  = 'welcome';
            $slug  = '404';
            $title = $this->title('', t('Page not found'));
        }

        if (empty($slug)) {
            $slug = 'welcome';
        } else {
            // try to get entity from slug
            $entity = Slug::find($slug);
            if (! $entity instanceof Slug) {
                //$page = '404';
            }

            $tableName = $_GET['table'] ?? 'pages';
            if ($slug === 'users') {
                $slug  = 'edit';
                $tableName = 'users';
            }

            if ($slug === 'edit') {
                $instances = [
                    'comments'    => App\Tables\Comments::class,
                    'translation' => App\Tables\Translations::class,
                    'emails'      => App\Tables\Emails::class,
                    'users'       => App\Tables\Users::class,
                    'pages'       => App\Tables\Pages::class,
                ];

                $table = new ($instances[$tableName] ?? App\Tables\Pages::class)();

                if ($tableName === 'files') {
                    $slug  = 'media';
                    $table = new App\Tables\Media();
                }

                if ($tableName === 'users') {
                    $slug  = 'edit';
                    $table = new App\Tables\Users();
                }
            }

            // output view to frontend
            $content = view($page ?? 'index', [
                'slug'   => $slug,
                'title'  => $title,
                'table'  => $table ?? null,
                'entity' => $entity,
            ]);

            // Auto-connect co-located CSS/JS for this page's template - see Manager::discover().
            Asset::discover($content->getPath());

            $content = $content->beautify()->render();
        }

        /**
         * Expansa page is fully loaded.
         *
         * @param string $content Current page content.
         * @param string $slug    Current page slug.
         */
        echo Hook::call('dashboardLoaded', $content ?? '', $slug);
    }

    /**
     * Document title: "{page} — {site name}". The page name is the label of the dashboard menu item
     * linking to the current URL, e.g. "Custom Fields" for "field-groups".
     */
    private function title(string $slug, ?string $page = null): string
    {
        $site = (string) Options::get('site.name', '');
        $site = $site !== '' ? $site : 'Expansa';

        if ($page === null && $slug !== '') {
            $query = isset($_GET['table']) ? $slug . '?table=' . $_GET['table'] : $slug;

            $page = match ($slug) {
                'sign-in'        => t('Sign In'),
                'sign-up'        => t('Sign Up'),
                'reset-password' => t('Reset password'),
                'installed'      => t('Installed'),
                default          => $this->menuTitle($query)
                    ?? $this->menuTitle($_GET['table'] ?? $slug)
                    ?? ucfirst(str_replace('-', ' ', $slug)),
            };
        }

        return $page === null || $page === '' ? $site : "$page — $site";
    }

    private function menuTitle(string $url): ?string
    {
        $tree = Tree::init();

        foreach (['dashboard-main-menu', 'dashboard-panel-menu', 'dashboard-user-menu'] as $menu) {
            foreach ($tree->list[$menu] ?? [] as $item) {
                if (($item['url'] ?? null) === $url && is_string($item['title'] ?? null) && $item['title'] !== '') {
                    return $item['title'];
                }
            }
        }

        return null;
    }
}
