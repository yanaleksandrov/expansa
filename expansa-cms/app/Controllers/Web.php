<?php

declare(strict_types=1);

namespace App\Controllers;

use App;
use App\Models\Slug;
use App\Models\User;
use Expansa\Facades\Hook;
use Expansa\Support\Is;

final class Web
{
    public function index($slug): void
    {
        /**
         * Expansa dashboard panel.
         *
         * @param string $slug Dashboard page root slug.
         */
        $dashboard = Hook::call('dashboardRootSlug', 'dashboard');

        // run the installer if Expansa is not installed.
        if (!Is::installed()) {
            if ($slug !== 'install') {
                redirect('install');
            }
            echo (new \Expansa\Support\Html())->beautify(view('welcome', ['slug' => 'install'])->render());
            exit;
        }

        // redirect unauthenticated users from the dashboard, but allow access to registration and password recovery.
        if (str_starts_with($slug, $dashboard)) {
            if (! User::isLogged()) {
                redirect('sign-in');
            }

            require_once EX_PATH . 'dashboard/index.php';

            $slug = str_replace('dashboard/', '', $slug);
        }

        // not allow some slugs for logged user, they are reserved.
        $blackListSlugs = ['install', 'sign-in', 'sign-up', 'reset-password'];
        if (in_array($slug, $blackListSlugs, true) && User::isLogged()) {
            redirect('dashboard');
        }

        // include & launch dashboard
        if (in_array($slug, ['sign-in', 'sign-up', 'reset-password'], true) && !User::isLogged()) {
            require_once EX_PATH . 'dashboard/index.php';

            $page = 'welcome';
        }

        if (empty($slug)) {
            $slug = 'welcome';
        } else {
            // try to get entity from slug
            $entity = Slug::get($slug);
            if (!$entity) {
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
                    $slug  = 'files';
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
                'table'  => $table ?? null,
                'entity' => $entity,
            ]);
            $content = (new \Expansa\Support\Html())->beautify($content->render());
        }

//    $start = microtime(true);
//    for ($i = 0; $i < 1; $i++) {
//        $key = App\Models\Apikey::get(1);
//    }
//    $end = microtime(true);
//    print_r($key);
//
//    $elapsed = $end - $start;
//    echo "Elapsed time: $elapsed seconds\n";
//    if ($key) {
//        $key->delete();
//    }
//    exit;

        /**
         * Expansa page is fully loaded.
         *
         * @param string $content Current page content.
         * @param string $slug    Current page slug.
         */
        echo Hook::call('dashboardLoaded', $content ?? '', $slug);
    }
}
