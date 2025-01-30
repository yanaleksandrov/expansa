<?php

declare(strict_types=1);

use App\User;
use Expansa\Facades\Hook;
use Expansa\Facades\Route;
use Expansa\Support\Is;
use Expansa\Security\Csrf\Csrf;

// Generate CSRF token.
(new Csrf())->generate('token');

//Route::middleware('/api', function () {
//
//    Route::post('/system/install', '\App\Api\System@install');
//});

Route::get('/(.*)', function ($slug) {
    $dashboardSlug  = ltrim(str_replace(EX_PATH, '/', EX_DASHBOARD), '/');

    // run the installer if Expansa is not installed.
    if (!Is::installed()) {
        if ($slug !== 'install') {
            redirect('install');
        }
        echo view('welcome', ['slug' => 'install']);
        exit;
    }

    // Redirect unauthenticated users from the dashboard, but allow access to registration and password recovery.
    // ! in_array($slug, ['sign-in', 'sign-up', 'reset-password'], true)
    if (str_starts_with($slug, $dashboardSlug) && ! User::logged()) {
        redirect('sign-in');
    }

    // Not allow some slugs for logged user, they are reserved.
    $blackListSlugs = ['install', 'sign-in', 'sign-up', 'reset-password'];
    if (in_array($slug, $blackListSlugs, true) && User::logged()) {
        redirect('dashboard');
    }

    if (in_array($slug, ['install', 'sign-in', 'sign-up', 'reset-password'], true) && !User::logged()) {
        /**
         * Launch dashboard.
         *
         * @since 2025.1
         */
        require_once EX_DASHBOARD . 'index.php';

        $page = 'welcome';
    }

//    $entity      = Slug::get($slug);
//    $entityId    = $entity['entity_id'] ?? 0;
//    $entityTable = $entity['entity_table'] ?? '';
//    if (! $entity && ( ! $entityId || ! $entityTable )) {
//        Route::trigger404();
//    }
//    $entity = Post::get($entityTable, $entityId);

    /**
     * The administrative panel also has a single entry point.
     *
     * @since 2025.1
     */
    $content = view($page ?? 'index', [
        'slug' => $slug,
    ]);
    $content = (new Expansa\Support\Html())->beautify($content->render());

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
 * Launch routing.
 *
 * @since 2025.1
 */
Route::run();
