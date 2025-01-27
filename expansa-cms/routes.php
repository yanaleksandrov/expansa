<?php

declare(strict_types=1);

use app\Post;
use app\Slug;
use app\User;
use Expansa\View;
use Expansa\Extensions;
use Expansa\Hook;
use Expansa\Is;
use Expansa\Route;

/**
 * Load private administrative panel.
 *
 * TODO: The dashboard must to be connected only if the current user is logged in & Is::ajax query.
 *
 * @since 2025.1
 */
$dashboardSlug  = trim(str_replace(EX_PATH, '/', EX_DASHBOARD), '/');
$dashboardRoute = sprintf('/%s/{slug}', $dashboardSlug);

Route::any($dashboardRoute, function ($slug) use ($dashboardSlug) {
    $query = new app\Query();

    $query->set('slug', sprintf('%s/%s', $dashboardSlug, $slug));
    $query->set(match ($slug) {
        'sign-in'        => 'isSignIn',
        'sign-up'        => 'isSignUp',
        'reset-password' => 'isResetPassword',
        default          => 'isDashboard',
    }, true);

    // Run the installer if Expansa is not installed.
    if ($slug !== 'install' && ! Is::installed()) {
        redirect('install');
    }

    // Redirect unauthenticated users from the dashboard, but allow access to registration and password recovery.
    if (! in_array($slug, ['sign-in', 'sign-up', 'reset-password'], true) && ! User::logged() && Is::installed()) {
        redirect('sign-in');
    }

    // Not allow some slugs for logged user, they are reserved.
    $blackListSlugs = ['install', 'sign-in', 'sign-up', 'reset-password'];
    if (in_array($slug, $blackListSlugs, true) && User::logged()) {
        redirect('dashboard');
    }

    /**
     * Launch dashboard.
     *
     * @since 2025.1
     */
    require_once EX_DASHBOARD . 'app/index.php';

    Extensions::boot('plugin');
    Extensions::boot('theme');

    /**
     * The administrative panel also has a single entry point.
     *
     * @since 2025.1
     */
    $content = View::make('index', [
        'slug' => $slug,
    ]);
    //$content = (new Expansa\Html())->beautify($content);

    /**
     * Expansa dashboard is fully loaded.
     *
     * @param string $content Current page content.
     * @param string $slug    Current page slug.
     *
     * @since 2025.1
     */
    echo Hook::call('dashboardLoaded', $content, $slug);
});

/**
 * None dashboard pages: website frontend output.
 *
 * @param string $slug Current page slug.
 *
 * @since 2025.1
 */
Route::get('/{slug}', function ($slug) {
    $query = new app\Query();

    $entity      = Slug::get($slug);
    $entityId    = $entity['entity_id'] ?? 0;
    $entityTable = $entity['entity_table'] ?? '';
    if (! $entity && ( ! $entityId || ! $entityTable )) {
        Route::trigger404();
    }

    $entity = Post::get($entityTable, $entityId);
//    echo '<pre>';
//    print_r($slug);
//    print_r($entity);
//    echo '</pre>';

    if (empty($slug)) {
        $query->set('isHome', true);
    }

    /**
     * Run the installer if Expansa is not installed.
     *
     * @since 2025.1
     */
    if (Is::installed() && $slug === 'install') {
        redirect('dashboard');
    }

    echo view('index.blade', [
        'slug' => $slug,
    ]);
});

/**
 * Launch routing.
 *
 * @since 2025.1
 */
Route::run();
