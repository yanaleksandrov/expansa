<?php

declare(strict_types=1);

// Load order: environment → phases → first matching context → fallback. See documentation/Lifecycle.md.
// No PHP 8.4 syntax in this file: on an older PHP it must still parse to show the requirements page.

use Expansa\Facades\Debug;
use Expansa\Facades\Extensions;
use Expansa\Facades\Form;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Facades\Lifecycle;
use Expansa\Facades\Route;
use Expansa\Facades\Safe;
use Expansa\Facades\Terminal;
use Expansa\Patterns\Registry;
use Expansa\Support\Is;

// =============================================================================
// 1. Environment
// =============================================================================

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.6';
const EX_REQUIRED_PHP_VERSION   = '8.4';
const EX_REQUIRED_MYSQL_VERSION = '8.0';
const EX_REQUIRED_MEMORY        = 128;

require_once EX_PATH . 'autoload.php';

// stops with an error page before any PHP 8.4 code is parsed
App\Support\Requirements::check();

require_once EX_PATH . 'expansa/functions.php';

// missing on a fresh copy until the installer creates it
if (is_file(EX_PATH . 'env.php')) {
    require_once EX_PATH . 'env.php';
}

metrics()->start();

// computed once: the install request itself changes the result
$installed = Is::installed();

// =============================================================================
// 2. Phases: run on every request in declaration order
// =============================================================================

// ---- boot: always -----------------------------------------------------------
Lifecycle::phase('boot', function () {
    if (Is::debug()) {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    }

    if (is_file($maintenance = EX_PATH . 'maintenance.php')) {
        require $maintenance;
    }

    // default data, loaded on first Registry::get()
    foreach (['countries', 'timezones', 'languages'] as $data) {
        Registry::lazy($data, fn () => require EX_PATH . "dashboard/data/$data.php");
    }
});

// ---- configure: always, no database -----------------------------------------
Lifecycle::phase('configure', function () {
    Hook::configure(EX_PATH . 'app/Listeners');

    Form::configure(
        [
            'text'            => Expansa\Builders\Forms\Fields\Input::class,
            'color'           => Expansa\Builders\Forms\Fields\Input::class,
            'date'            => Expansa\Builders\Forms\Fields\Input::class,
            'datetime-local'  => Expansa\Builders\Forms\Fields\Input::class,
            'email'           => Expansa\Builders\Forms\Fields\Input::class,
            'month'           => Expansa\Builders\Forms\Fields\Input::class,
            'range'           => Expansa\Builders\Forms\Fields\Input::class,
            'search'          => Expansa\Builders\Forms\Fields\Input::class,
            'tel'             => Expansa\Builders\Forms\Fields\Input::class,
            'time'            => Expansa\Builders\Forms\Fields\Input::class,
            'url'             => Expansa\Builders\Forms\Fields\Input::class,
            'week'            => Expansa\Builders\Forms\Fields\Input::class,

            'builder'         => Expansa\Builders\Forms\Fields\Builder::class,
            'checkbox'        => Expansa\Builders\Forms\Fields\Checkbox::class,
            'custom'          => Expansa\Builders\Forms\Fields\Custom::class,
            'details'         => Expansa\Builders\Forms\Fields\Details::class,
            'divider'         => Expansa\Builders\Forms\Fields\Divider::class,
            'file'            => Expansa\Builders\Forms\Fields\File::class,
            'header'          => Expansa\Builders\Forms\Fields\Header::class,
            'hidden'          => Expansa\Builders\Forms\Fields\Hidden::class,
            'image'           => Expansa\Builders\Forms\Fields\Image::class,
            'input'           => Expansa\Builders\Forms\Fields\Input::class,
            'layout-group'    => Expansa\Builders\Forms\Fields\LayoutGroup::class,
            'layout-step'     => Expansa\Builders\Forms\Fields\LayoutStep::class,
            'layout-tab'      => Expansa\Builders\Forms\Fields\LayoutTab::class,
            'layout-tab-menu' => Expansa\Builders\Forms\Fields\LayoutTabMenu::class,
            'media'           => Expansa\Builders\Forms\Fields\Media::class,
            'number'          => Expansa\Builders\Forms\Fields\Number::class,
            'password'        => Expansa\Builders\Forms\Fields\Password::class,
            'progress'        => Expansa\Builders\Forms\Fields\Progress::class,
            'radio'           => Expansa\Builders\Forms\Fields\Radio::class,
            'select'          => Expansa\Builders\Forms\Fields\Select::class,
            'submit'          => Expansa\Builders\Forms\Fields\Submit::class,
            'textarea'        => Expansa\Builders\Forms\Fields\Textarea::class,
            'uploader'        => Expansa\Builders\Forms\Fields\Uploader::class,

            'editor'          => Expansa\Builders\Forms\Fields\Editor::class,
            'gallery'         => Expansa\Builders\Forms\Fields\Gallery::class,
            'repeater'        => Expansa\Builders\Forms\Fields\Repeater::class,
            'message'         => Expansa\Builders\Forms\Fields\Message::class,
        ]
    );

    // translation lookup priority; the paths come from env.php, missing before install
    if (defined('EX_CORE')) {
        I18n::configure(
            [
                EX_CORE      => EX_DASHBOARD,
                EX_DASHBOARD => EX_DASHBOARD,
                EX_PLUGINS   => EX_PLUGINS . ':dirname',
                EX_THEMES    => EX_THEMES . ':dirname',
            ],
            'i18n/%s'
        );
    }
});

// ---- register: installed only, creates missing tables -----------------------
Lifecycle::phase('register', function () {
    App\User\Roles::register('admin', t('Administrator'), [
        'read',
        'files_upload',
        'files_edit',
        'files_delete',
        'types_publish',
        'types_edit',
        'types_delete',
        'other_types_publish',
        'other_types_edit',
        'other_types_delete',
        'private_types_publish',
        'private_types_edit',
        'private_types_delete',
        'manage_comments',
        'manage_options',
        'manage_update',
        'manage_import',
        'manage_export',
        'themes_install',
        'themes_switch',
        'themes_delete',
        'plugins_install',
        'plugins_activate',
        'plugins_delete',
        'users_create',
        'users_edit',
        'users_delete',
    ]);

    App\User\Roles::register('editor', t('Editor'), [
        'read',
        'files_upload',
        'files_edit',
        'files_delete',
        'types_publish',
        'types_edit',
        'types_delete',
        'other_types_publish',
        'other_types_edit',
        'other_types_delete',
        'private_types_publish',
        'private_types_edit',
        'private_types_delete',
        'manage_comments',
    ]);

    App\User\Roles::register('author', t('Author'), [
        'read',
        'files_upload',
        'files_edit',
        'files_delete',
        'types_publish',
        'types_edit',
        'types_delete',
    ]);

    App\User\Roles::register('subscriber', t('Subscriber'), [
        'read',
    ]);

    App\Post\Type::register(
        key: 'pages',
        labelName: t('Page'),
        labelNamePlural: t('Pages'),
        labelAllItems: t('All Pages'),
        labelAdd: t('Add New'),
        labelEdit: t('Edit Page'),
        labelUpdate: t('Update Page'),
        labelView: t('View Page'),
        labelSearch: t('Search Pages'),
        labelSave: t('Save Page'),
        public: true,
        hierarchical: false,
        searchable: true,
        showInMenu: true,
        showInBar: true,
        canExport: true,
        canImport: true,
        capabilities: ['types_edit'],
        menuIcon: 'ph ph-folders',
        menuPosition: 20,
    );

    App\Post\Type::register(
        key: 'files',
        labelName: t('Storage'),
        labelNamePlural: t('Storage'),
        labelAllItems: t('Library'),
        labelAdd: t('Upload'),
        labelEdit: t('Edit Media'),
        labelUpdate: t('Update Media'),
        labelView: t('View Media'),
        labelSearch: t('Search Media'),
        labelSave: t('Save Media'),
        public: true,
        hierarchical: false,
        searchable: false,
        showInMenu: true,
        showInBar: false,
        canExport: true,
        canImport: true,
        capabilities: ['types_edit'],
        menuIcon: 'ph ph-dropbox-logo',
        menuPosition: 30,
    );

    App\Post\Type::register(
        key: 'api-keys',
        labelName: t('API Key'),
        labelNamePlural: t('API Keys'),
        labelAllItems: t('All API Keys'),
        labelAdd: t('Add New Key'),
        labelEdit: t('Edit Key'),
        labelUpdate: t('Update Key'),
        labelView: t('View Key'),
        labelSearch: t('Search Keys'),
        labelSave: t('Save Key'),
        public: false,
        hierarchical: false,
        searchable: false,
        showInMenu: false,
        showInBar: false,
        canExport: true,
        canImport: true,
        capabilities: ['types_edit'],
        menuIcon: 'ph ph-key',
        menuPosition: 30,
    );
}, fn () => $installed);

// ---- extensions: installed only ---------------------------------------------
Lifecycle::phase('extensions', function () {
    // ids like "plugins/seo"
    Extensions::enqueue(fn () => Extensions::paths((array) App\Models\Options::get('extensions.active', [])));
    Extensions::register('plugin');
    Extensions::register('theme');
}, fn () => $installed);

// ---- booted: installed only, every extension is registered ------------------
Lifecycle::phase('booted', function () {
    Extensions::boot('plugin');
    Extensions::boot('theme');
}, fn () => $installed);

// =============================================================================
// 3. Contexts: only the first match runs, so the order matters
// =============================================================================

// ---- cli --------------------------------------------------------------------
Lifecycle::context('cli', fn () => PHP_SAPI === 'cli', function () {
    Terminal::handle();
});

// ---- api --------------------------------------------------------------------
// before install: the installer calls system/test and system/install
Lifecycle::context('api', fn (string $uri) => str_starts_with($uri, '/api/'), function () {
    Route::before('*', '/api/.*', function () {
        header('Content-Type: application/json; charset=utf-8');
    });

    // safe methods (GET) are exempt from CSRF by convention
    Route::before('POST|PUT|PATCH|DELETE', '/api/.*', [App\Http\VerifyCsrfToken::class, 'handle']);

    // RequireAuth has its own allow-list: system/test, system/install, user/sign-in, user/sign-up, user/reset-password
    Route::before('*', '/api/.*', [App\Http\RequireAuth::class, 'handle']);

    Route::prefix('/api', function () {
        // reference implementation: routes derived by Router::register() (POST /system/test, POST /system/install)
        Route::register(App\Api\System\SystemController::class, [App\Http\Kernel::class, 'dispatch']);

        // RPC routes instead of Router::register(): the dashboard calls fixed URLs like `apikey/create` with the id in the body
        foreach (
            [
                App\Api\Apikey\ApikeyController::class,
                App\Api\Extensions\ExtensionsController::class,
                App\Api\FieldGroups\FieldGroupsController::class,
                App\Api\Files\FilesController::class,
                App\Api\Media\MediaController::class,
                App\Api\Options\OptionsController::class,
                App\Api\Post\PostController::class,
                App\Api\Posts\PostsController::class,
                App\Api\Translations\TranslationsController::class,
                App\Api\User\UserController::class,
            ] as $class
        ) {
            $prefix = Safe::kebabcase(preg_replace('/Controller$/', '', (new ReflectionClass($class))->getShortName()));

            foreach (get_class_methods($class) as $method) {
                if ($method === '__construct') {
                    continue;
                }

                $endpoint = Safe::kebabcase($method);

                Route::post("/$prefix/$endpoint", fn (...$params) => App\Http\Kernel::dispatch($class, $method, $params));
            }
        }
    });
});

// ---- install ----------------------------------------------------------------
// until installed, every other page shows the installer
Lifecycle::context('install', fn () => !$installed, function () {
    require_once EX_PATH . 'dashboard/install.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'install']);
});

// ---- auth -------------------------------------------------------------------
Lifecycle::context('auth', fn (string $uri) => in_array(trim($uri, '/'), ['sign-in', 'sign-up', 'reset-password'], true), function () {
    if (App\Models\User::isLogged()) {
        redirect('dashboard');
    }

    require_once EX_PATH . 'dashboard/auth.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});

// ---- dashboard --------------------------------------------------------------
// the slug can be changed with the "dashboardRootSlug" hook
Lifecycle::context('dashboard', fn (string $uri) => str_starts_with(trim($uri, '/'), Hook::call('dashboardRootSlug', 'dashboard')), function () {
    if (! App\Models\User::isLogged()) {
        redirect('sign-in');
    }

    require_once EX_PATH . 'dashboard/index.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});

// ---- web: everything else ---------------------------------------------------
Lifecycle::context('web', fn () => true, function () {
    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});

// =============================================================================
// 4. Run: fallback after the context, errors from any step go to the debug page
// =============================================================================

Lifecycle::fallback(function () {
    if (! Lifecycle::is('cli')) {
        Route::run();
    }
});

// EX_DEBUG_VIEW comes from env.php, which may not exist yet
Lifecycle::catch(fn (Throwable $e) => Debug::render($e, defined('EX_DEBUG_VIEW') ? EX_DEBUG_VIEW : EX_PATH . 'dashboard/debug.php'));

Lifecycle::run();
