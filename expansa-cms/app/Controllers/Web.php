<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Facades\Dashboard;
use Expansa\Facades\Asset;
use Expansa\Facades\Auth;
use Expansa\Facades\Hook;
use Expansa\Facades\Lifecycle;
use Expansa\Http\Request;

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

        $welcome = view('guest', ['page' => 'screens/install', 'title' => t('Install Expansa')]);

        Asset::discover($welcome->path);

        echo $welcome->beautify()->render();
    }

    /**
     * Pages of the "auth", "dashboard" and "web" lifecycle contexts; access checks and assets are done by the context,
     * the page itself by Dashboard::render().
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
        if (in_array($slug, $blackListSlugs, true) && Auth::isLoggedIn() && ! ($slug === 'sign-in' && isset($_GET['add']))) {
            redirect('dashboard');
        }

        $layout = Lifecycle::is('auth') ? 'guest' : 'welcome';

        // dashboard views are not public pages: outside the dashboard only the front page and the post-install page exist
        if (Lifecycle::is('web') && $slug !== '' && ! ($slug === 'installed' && Auth::isLoggedIn())) {
            $layout = 'guest';
            $slug   = '404';
        }

        /**
         * Expansa page is fully loaded.
         *
         * @param string $content Current page content.
         * @param string $slug    Current page slug.
         */
        echo Hook::call('dashboardLoaded', $slug === '' ? '' : Dashboard::render($slug, Request::createFromGlobals(), $layout), $slug ?: 'welcome');
    }
}
