<?php

declare(strict_types=1);

use App\User;
use Expansa\Error;
use Expansa\Facades\Safe;
use Expansa\Facades\Hook;
use Expansa\Facades\Route;
use Expansa\Security\Csrf\Providers\NativeHttpOnlyCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;
use Expansa\Support\Is;
use Expansa\Security\Csrf\Csrf;

Route::middleware('/api', function () {

    header('Content-Type: application/json; charset=utf-8');

    $csrf = new Csrf(new NativeHttpOnlyCookieProvider());
    try {
        $csrf->check('token', $_COOKIE['expansa_token'] ?? '');
    } catch (InvalidCsrfTokenException $e) {
        $data = new Error('api-no-route', t('Ajax queries not allows without CSRF token!'));
    }

    // generate CSRF token.
    $csrf->generate('token');

    foreach (
        [
            App\Api\Extensions::class,
            App\Api\Files::class,
            App\Api\Media::class,
            App\Api\Option::class,
            App\Api\Post::class,
            App\Api\Posts::class,
            App\Api\System::class,
            App\Api\Translations::class,
            App\Api\User::class,
        ] as $class
    ) {
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            $classname = (new ReflectionClass($class))->getShortName();
            $prefix    = Safe::lowercase($classname);
            $endpoint  = Safe::kebabcase($method);

            Route::post("/$prefix/$endpoint", [$class, $method]);
        }
    }
});

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
