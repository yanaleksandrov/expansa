<?php

declare(strict_types=1);

/**
 * Core load order, top to bottom: 1. environment, 2. phases, 3. contexts (first match only), 4. run.
 * No PHP 8.4 syntax in this file: on an older PHP it must still parse to show the requirements page.
 *
 * @see documentation/Lifecycle.md
 */

use Expansa\Facades\Db;
use Expansa\Facades\Debug;
use Expansa\Facades\Extensions;
use Expansa\Facades\Form;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Facades\Lifecycle;
use Expansa\Facades\Route;
use Expansa\Facades\Safe;
use Expansa\Facades\Terminal;
use Expansa\Facades\View;
use Expansa\Patterns\Registry;
use Expansa\Support\Is;
use Expansa\Support\Url;

/**
 * 1. Environment
 *
 * Constants, env.php, autoload and helpers, then the PHP check and the settings the phases need first.
 */

const EX_PATH                   = __DIR__ . '/';
const EX_VERSION                = '2025.6';
const EX_REQUIRED_PHP_VERSION   = '8.4';
const EX_REQUIRED_MYSQL_VERSION = '8.0';
const EX_REQUIRED_MEMORY        = 128;

// the code layout, the same for every installation, so env.php can already use it
const EX_CORE      = EX_PATH . 'expansa/';
const EX_DASHBOARD = EX_PATH . 'dashboard/';
const EX_PLUGINS   = EX_PATH . 'plugins/';
const EX_THEMES    = EX_PATH . 'themes/';
const EX_STORAGE   = EX_PATH . 'storage/';
const EX_I18N      = EX_PATH . 'i18n/';

// missing on a fresh copy until the installer creates it
if (is_file(EX_PATH . 'env.php')) {
    require_once EX_PATH . 'env.php';
}

require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'expansa/functions.php';

// stops with an error page before any PHP 8.4 code is parsed: everything above must stay free of it
App\Support\Requirements::check();

// needed before the phases: boot reads Is::debug()
Is::configure(
    debug: defined('EX_DEBUG') && EX_DEBUG['enabled'] === true
);

// computed once: the installation request itself changes the result
$isInstalled = App\Support\Installation::isComplete();

/**
 * 2. Phases
 *
 * Every request, in declaration order; the ones bound to $isInstalled are skipped before install.
 */

/**
 * 1. boot · always.
 *
 * Turns on error output in debug mode and stops on maintenance.php, if it exists.
 * Registers the default data (countries, timezones, languages), loaded on first Registry::get().
 */
Lifecycle::phase('boot', true, function () {
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

/**
 * 2. configure · always, also before install, so no database queries here.
 *
 * Passes the database, site URL, views, extensions root, console version and table filter to the framework,
 * then the translations priority, the hook listener classes and the form field types.
 */
Lifecycle::phase('configure', true, function () {
    // the connection from env.php; nothing to connect to before install
    if (defined('EX_DB')) {
        Db::configure(...EX_DB);
    }

    // the site URL is read from the options only once there is a database to read it from
    Url::configure(
        root: EX_PATH,
        site: defined('EX_DB') ? fn () => App\Models\Options::get('site.url') : null,
    );

    // views of the dashboard, installer and auth pages
    View::configure(
        viewsPath: EX_PATH . 'dashboard/views',
        cachePath: EX_PATH . 'cache/views',
    );

    // extension ids like "plugins/seo" are relative to it
    Extensions::configure(
        root: EX_PATH
    );

    // the version shown by the "list" console command
    Terminal::configure(version: EX_VERSION);

    // every dashboard table renders the items filter form
    Expansa\Builders\Table::configure(
        filter: EX_DASHBOARD . 'forms/items-filter.php'
    );

    // translations lookup priority
    I18n::configure(
        routes: [
            EX_CORE      => EX_DASHBOARD,
            EX_DASHBOARD => EX_DASHBOARD,
            EX_PLUGINS   => EX_PLUGINS . ':dirname',
            EX_THEMES    => EX_THEMES . ':dirname',
        ],
        pattern: 'i18n/%s',
        overrides: EX_I18N,
    );

    // a new listener class has to be added here
    Hook::configure(
        listeners: [
            App\Listeners\Assets::class,
            App\Listeners\Migrations::class,
        ],
    );

    // form fields: input types, basic fields, composite fields
    Form::configure(
        fields: [
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
        ],
    );
});

/**
 * 3. register · installed only: needs env.php and the database.
 *
 * Registers the default roles (admin, editor, author, subscriber) and post types
 * (pages, files, api-keys); post types create their missing tables.
 */
Lifecycle::phase('register', $isInstalled, function () {
    // roles
    App\User\Roles::register(
        role: 'admin',
        displayName: t('Administrator'),
        capabilities: [
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
        ],
    );

    App\User\Roles::register(
        role: 'editor',
        displayName: t('Editor'),
        capabilities: [
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
        ],
    );

    App\User\Roles::register(
        role: 'author',
        displayName: t('Author'),
        capabilities: [
            'read',
            'files_upload',
            'files_edit',
            'files_delete',
            'types_publish',
            'types_edit',
            'types_delete',
        ],
    );

    App\User\Roles::register(
        role: 'subscriber',
        displayName: t('Subscriber'),
        capabilities: [
            'read',
        ],
    );

    // post types
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
});

/**
 * 4. extensions · installed only.
 *
 * Loads the active plugins & themes listed in the "extensions.active" option (ids like "plugins/seo")
 * and calls register() on each: plugins first, then themes.
 */
Lifecycle::phase('extensions', $isInstalled, function () {
    Extensions::load(
        ids: (array) App\Models\Options::get('extensions.active', []),
    );
    Extensions::register('plugin');
    Extensions::register('theme');
});

/**
 * 5. booted · installed only.
 *
 * Every extension is registered, so boot() runs on plugins, then themes.
 * From here Is::dashboard() and other context checks are available.
 */
Lifecycle::phase('booted', $isInstalled, function () {
    Extensions::boot('plugin');
    Extensions::boot('theme');
});

/**
 * 3. Contexts
 *
 * After the phases only the first match runs, so the order matters.
 */

/**
 * 1. cli · console, run through artisan.
 *
 * The terminal runs the requested command and prints its output.
 * No routing: run() skips it in the console.
 */
Lifecycle::context('cli', PHP_SAPI === 'cli', function () {
    Terminal::addCommand(App\Console\Serve::class);
    Terminal::run();
});

/**
 * 2. api · URI starts with /api/, declared before install: the installer calls system/test and system/install.
 *
 * Adds the JSON header, the CSRF check for mutating methods and the auth check, then the routes:
 * SystemController through Router::register(), the other controllers as POST /{controller}/{method}.
 */
Lifecycle::context('api', fn (string $uri) => str_starts_with($uri, '/api/'), function () {
    // middlewares, run in this order
    Route::before('*', '/api/.*', function () {
        header('Content-Type: application/json; charset=utf-8');
    });

    // safe methods (GET) are exempt from CSRF by convention
    Route::before('POST|PUT|PATCH|DELETE', '/api/.*', [App\Http\VerifyCsrfToken::class, 'handle']);

    // RequireAuth has its own allow-list: system/test, system/install, user/sign-in, user/sign-up, user/reset-password
    Route::before('*', '/api/.*', [App\Http\RequireAuth::class, 'handle']);

    // routes
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

/**
 * 3. install · not installed yet.
 *
 * Every page except the API shows the installer: its assets come from dashboard/install.php,
 * the page from Web::install().
 */
Lifecycle::context('install', !$isInstalled, function () {
    require_once EX_PATH . 'dashboard/install.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'install']);
});

/**
 * 4. auth · sign-in, sign-up and reset-password pages.
 *
 * A logged-in user goes straight to the dashboard. Otherwise, dashboard/auth.php enqueues
 * only the assets the auth forms need, without the admin panel.
 */
Lifecycle::context('auth', fn (string $uri) => in_array(trim($uri, '/'), ['sign-in', 'sign-up', 'reset-password'], true), function () {
    if (App\Models\User::isLogged()) {
        redirect('dashboard');
    }

    require_once EX_PATH . 'dashboard/auth.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});

/**
 * 5. dashboard · URI starts with the dashboard slug.
 *
 * The slug is "dashboard" by default, changed with the "dashboardRootSlug" hook. A guest is redirected
 * to sign-in, otherwise dashboard/index.php enqueues the assets and menus, the page comes from Web::index().
 */
Lifecycle::context('dashboard', fn (string $uri) => str_starts_with(trim($uri, '/'), Hook::call('dashboardRootSlug', 'dashboard')), function () {
    if (! App\Models\User::isLogged()) {
        redirect('sign-in');
    }

    require_once EX_PATH . 'dashboard/index.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});

/**
 * 6. web · everything else.
 *
 * The public site: the home page and /installed through Web::index(), otherwise 404.
 */
Lifecycle::context('web', true, function () {
    Route::get('/(.*)', [App\Controllers\Web::class, 'index']);
});

/**
 * 4. Run
 *
 * Phases, the matched context, then its routes (not in the console).
 * Errors from any step go to the debug page.
 */
Lifecycle::run(catch: function (Throwable $e) {
    // EX_DEBUG comes from env.php, which may be missing
    $view = defined('EX_DEBUG') ? EX_DEBUG['view'] : EX_DASHBOARD . 'debug.php';

    Debug::render($e, $view);
});
