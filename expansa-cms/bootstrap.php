<?php

declare(strict_types=1);

/**
 * Core load order, top to bottom: 1. environment, 2. phases, 3. contexts (first match only), 4. run.
 * No PHP 8.4 syntax in this file: on an older PHP it must still parse to show the requirements page.
 *
 * @see documentation/Lifecycle.md
 */

use Expansa\Facades\Access;
use Expansa\Facades\Asset;
use Expansa\Facades\Auth;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;
use Expansa\Facades\Debug;
use Expansa\Facades\Extensions;
use Expansa\Facades\Form;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Facades\Lifecycle;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\Role;
use Expansa\Facades\Route;
use Expansa\Facades\Safe;
use Expansa\Facades\Session;
use Expansa\Facades\Terminal;
use Expansa\Facades\View;
use Expansa\Patterns\Registry;
use Expansa\Scheduler\Scheduler;
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

// as in WordPress: "enabled" is the main switch, "log" and "display" work only under it;
// true, 1, "1", "on" and "yes" count as true: env.php is written by hand as often as by the installer
$debug = (defined('EX_DEBUG') ? EX_DEBUG : []) + ['enabled' => false, 'log' => true, 'display' => true, 'view' => EX_DASHBOARD . 'debug.php'];
$isDebug     = filter_var($debug['enabled'], FILTER_VALIDATE_BOOL);
$isLogged    = $isDebug && filter_var($debug['log'], FILTER_VALIDATE_BOOL);
$isDisplayed = $isDebug && filter_var($debug['display'], FILTER_VALIDATE_BOOL);

// needed before the phases: boot reads Is::debug(); the dashboard context is known only after the phases
Is::configure(
    debug: $isDebug,
    dashboard: fn () => Lifecycle::is('dashboard'),
    // env.php and the owner in the database, checked once after the configure phase connects it: the installation request changes the result
    installed: function () {
        static $installed;

        return $installed ??= App\Support\Installation::isComplete();
    },
);

// every PHP error is reported in debug mode, and thrown by the handler below
if (Is::debug()) {
    error_reporting(E_ALL);
}

// before the phases, so an error in any of them reaches the log and the error page; without debug the page shows only the id
Debug::configure(
    view: $debug['view'],
    details: $isDisplayed,
    strict: $isDebug,
    report: $isLogged ? function (Throwable $e, string $id, array $context) {
        // out of memory, time limit, compile error: critical, to alert on it separately
        Log::log(Expansa\Debug\Manager::isFatal($e) ? 'critical' : 'error', 'Uncaught {class}: {message}', [
            'id'        => $id,
            'class'     => get_class($e),
            'message'   => $e->getMessage(),
            'exception' => $e,
        ] + $context);
    } : null,
    warning: $isLogged ? fn (ErrorException $e) => Log::warning('{message} in {file}:{line}', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]) : null,
    // a Closure value is resolved on its own: a failing one does not lose the rest
    context: fn () => PHP_SAPI === 'cli' ? ['command' => implode(' ', $_SERVER['argv'] ?? [])] : [
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'url'    => ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
        'user'   => fn () => defined('EX_DB') ? App\Models\User::current()?->id : null,
        'input'  => $_POST,
    ],
    json: fn () => str_starts_with(Route::uri(), '/api/') || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json'),
    collapse: [EX_CORE, EX_PATH . 'vendor/'],
);
Debug::register();

// step hooks, the terminate hook after the response, routing after the context
Lifecycle::configure(
    hook: fn (string $name) => Hook::call($name),
    terminate: fn () => Hook::defer('terminate'),
    route: fn () => Route::run(),
    uri: fn () => Route::uri(),
);

/**
 * 2. Phases
 *
 * Every request, in declaration order; the ones bound to Is::installed() are skipped before install.
 */

/**
 * 1. boot · always.
 *
 * Stops on maintenance.php, if it exists.
 * Registers the default data (countries, timezones, languages), loaded on first Registry::get().
 */
Lifecycle::phase('boot', true, function () {
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
 * Passes the database, site URL, views, extensions root, console version, mail and scheduler, redirect filters and table filter
 * to the framework, then the translations priority, the hook listener classes and the form field types.
 */
Lifecycle::phase('configure', true, function () {
    // the connection from env.php; nothing to connect to before install
    if (defined('EX_DB')) {
        // every query is kept for the debug panel in debug mode; not in the console, where a long process would pile them up
        $logging = (EX_DB['logging'] ?? false) || (Is::debug() && PHP_SAPI !== 'cli');
        Db::configure(...array_merge(EX_DB, ['logging' => $logging]));
    }

    // the stores from env.php, the request memory without them; env.php can not hold the connection closure
    if (defined('EX_CACHE')) {
        $stores = array_map(
            fn (array $store) => $store['driver'] === 'database' ? $store + ['connection' => fn () => Db::instance()] : $store,
            EX_CACHE['stores']
        );

        Cache::configure($stores, EX_CACHE['default']);
    }

    // models cache rows in the default cache store, sanitize and validate by the Security rules
    Expansa\Database\Model::configure(
        cache: fn (string $key, string $group, ?Closure $callback = null) => Cache::get($key, $group, $callback),
        forgetCache: fn (string $key, string $group) => Cache::forget($key, $group),
        sanitizer: fn (array $data, array $rules) => Safe::data($data, $rules)->apply(),
        validator: fn (array $data, array $rules, bool $break) => new Expansa\Security\Validator($data, $rules, $break),
    );

    // the site URL is read from the options only once there is a database to read it from
    Url::configure(
        root: EX_PATH,
        site: defined('EX_DB') ? fn () => App\Models\Option::get('site.url') : null,
    );

    // views of the dashboard, installer and auth pages
    View::configure(
        paths: EX_PATH . 'dashboard/views',
        cachePath: EX_PATH . 'cache/views',
    );

    // extension ids like "plugins/seo" are relative to it; a plugin that breaks goes to quarantine instead of the site
    Extensions::configure(
        root: EX_PATH,
        quarantine: EX_STORAGE . 'quarantine.json',
        failed: fn (string $id, Throwable $error) => Log::error('Extension {id} failed: {message}', [
            'id'      => $id,
            'message' => $error->getMessage(),
            'file'    => $error->getFile(),
            'line'    => $error->getLine(),
        ]),
    );

    // Log::info() and the others: a file per day in storage/logs, closed to the web, kept for two weeks
    Log::configure([
        'daily' => [
            'driver' => 'daily',
            'path'   => EX_STORAGE . 'logs/expansa.log',
            'days'   => 14,
            'level'  => Is::debug() ? 'debug' : 'info',
        ],
    ]);

    // the signed-in user comes from the auth cookie: a new password or EX_KEYS['auth'] signs everyone out;
    // the device cookie marks browsers that signed in before; throttling and providers come from the Security settings
    $cookiePrefix = defined('EX_DB') ? EX_DB['prefix'] : '';
    Auth::configure(
        // a disabled account loses its tokens at once
        find: function (string $login): ?App\Models\User {
            $user = App\Models\User::find($login, 'login');

            return $user instanceof App\Models\User && $user->status === App\Models\User::STATUS_ACTIVE ? $user : null;
        },
        key: defined('EX_KEYS') ? EX_KEYS['auth'] : '',
        read: fn (string $name) => (string) Expansa\Cookie\Cookie::get($cookiePrefix . $name, ''),
        write: fn (string $name, string $value, int $expires) => Expansa\Cookie\Cookie::send(new Expansa\Cookie\Cookie(
            name: $cookiePrefix . $name,
            value: $value,
            expires: $expires,
            path: '/',
            secure: Expansa\Cookie\Cookie::isSecureRequest(),
            httpOnly: true,
            sameSite: Expansa\Cookie\Enums\SameSite::Lax,
        )),
        // providers with a client ID return to /oauth/<name>/callback
        providers: function (): array {
            $providers = [];
            foreach ((array) App\Models\Option::get('oauth', []) as $name => $config) {
                if (is_array($config) && trim((string) ($config['client_id'] ?? '')) !== '') {
                    $providers[$name] = $config + ['redirect' => url("oauth/$name/callback")];
                }
            }

            return $providers;
        },
        // failed passwords in the "cache" table: the default store may be the request memory
        throttle: fn () => [
            'attempts'    => (int) App\Models\Option::get('security.attempts', 5),
            'ip_attempts' => (int) App\Models\Option::get('security.ip_attempts', 50),
            'lockout'     => (int) App\Models\Option::get('security.lockout', 15) * 60,
        ],
        readAttempts: fn (string $key) => (new Expansa\Cache\Providers\Database(Db::instance()))->get($key, 'auth-attempts'),
        writeAttempts: function (string $key, ?array $attempts, int $ttl): void {
            $cache = new Expansa\Cache\Providers\Database(Db::instance());
            $cache->forget($key, 'auth-attempts');

            if ($attempts !== null) {
                $cache->add($key, $attempts, 'auth-attempts', "+$ttl seconds");
            }
        },
        // a row per signed-in device, so the profile can sign out any of them
        sessions: new App\Api\User\Sessions(),
        // personal API tokens: Authorization: Bearer exp_..., see App\Api\User\Tokens
        bearer: fn () => preg_match('/^Bearer\s+(\S+)$/i', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''), $match) ? $match[1] : null,
        findToken: fn (string $token) => App\Api\User\Tokens::authenticate($token),
    );

    // permissions come from the roles of Role::add(); plugins add policies of their resources with Access::setPolicy()
    $roles = new Expansa\Access\Roles();
    Role::swap($roles);
    Access::configure(
        // a request with an API token gets only the permissions of its scopes
        permissions: new App\Support\TokenPermissions($roles),
        policies: [App\Models\Post::class => App\Post\Policy::class],
    );

    // menu items and their pages need every capability of the item
    Expansa\Builders\Tree::configure(
        allows: fn (array $capabilities) => array_all($capabilities, fn (string $capability) => Access::allows(App\Models\User::current(), $capability)),
    );

    // the version shown by the "list" console command
    Terminal::configure(
        version: EX_VERSION
    );

    // every email gets the site sender and DKIM signature, then the "mailer" filter: SMTP settings, a test double
    Mail::configure(
        setup: fn (PHPMailer\PHPMailer\PHPMailer $mailer) => Hook::call('mailer', App\Support\Mailer::setup($mailer)),
    );

    // scheduled jobs keep their locks in the storage and email their output through Mail
    Scheduler::configure(
        tempDir: EX_STORAGE,
        mailer: fn (string $to, string $subject, string $body, array $attachments) => Mail::send($to, $subject, $body, $attachments),
    );

    // the native session in the browser, an array in the console; started only by the code that needs it
    Session::configure(
        driver: PHP_SAPI === 'cli' ? 'memory' : 'native',
        options: [
            'name'   => 'expansa',
            'secure' => Expansa\Cookie\Cookie::isSecureRequest(),
        ],
    );

    // redirect location, status and X-Redirect-By header are filtered by hooks; flashed values go to the session
    Expansa\Http\Redirect::configure(
        location: fn (string $to, int $status) => Hook::call('redirectLocation', $to, $status),
        status: fn (int $status, string $to) => Hook::call('redirectStatus', $status, $to),
        redirectBy: fn (string $redirectBy, int $status, string $to) => Hook::call('redirectBy', $redirectBy, $status, $to),
        flash: function (string $key, array $values) {
            if (! Session::isStarted()) {
                Session::start();
            }

            Session::getFlash()->set($key, $values);
        },
    );

    // every dashboard table renders the items filter form
    Expansa\Builders\Table\AbstractTable::configure(
        filter: EX_DASHBOARD . 'forms/items-filter.php'
    );

    // validation error messages in the site language
    Expansa\Security\Validator::configure(
        translate: fn (string $message, string ...$args) => t($message, ...$args)
    );

    // translations lookup priority; the languages with their plural rules, extended by the "languages" hook
    I18n::configure(
        routes: [
            EX_CORE      => EX_DASHBOARD,
            EX_DASHBOARD => EX_DASHBOARD,
            EX_PLUGINS   => EX_PLUGINS . ':dirname',
            EX_THEMES    => EX_THEMES . ':dirname',
        ],
        pattern: 'i18n/%s',
        overrides: EX_I18N,
        languages: fn () => Hook::call('languages', Registry::get('languages')),
    );

    // a new listener class has to be added here
    Hook::configure(
        listeners: [
            App\Listeners\Assets::class,
            App\Listeners\Debug::class,
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
        view: fn (string $template, array $data) => (string) View::create($template, $data),
        assets: fn (string $template, string $uid) => Asset::discover(View::create($template)->path, $uid, ['type' => $uid]),
    );
});

/**
 * 3. register · installed only: needs env.php and the database.
 *
 * Registers the default roles (admin, editor, author, subscriber) and post types
 * (pages, files, api-keys); post types create their missing tables.
 */
Lifecycle::phase('register', fn () => Is::installed(), function () {
    // roles
    Role::add(
        role: 'admin',
        name: t('Administrator'),
        permissions: [
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

    Role::add(
        role: 'editor',
        name: t('Editor'),
        permissions: [
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

    Role::add(
        role: 'author',
        name: t('Author'),
        permissions: [
            'read',
            'files_upload',
            'files_edit',
            'files_delete',
            'types_publish',
            'types_edit',
            'types_delete',
        ],
    );

    Role::add(
        role: 'subscriber',
        name: t('Subscriber'),
        permissions: [
            'read',
        ],
    );

    // roles changed or added in the Settings replace the defaults above
    App\Support\RoleSettings::apply();

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
Lifecycle::phase('extensions', fn () => Is::installed(), function () {
    Extensions::load(
        ids: (array) App\Models\Option::get('extensions.active', []),
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
Lifecycle::phase('booted', fn () => Is::installed(), function () {
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
 * Adds the commands of the app and the packages, then the terminal runs the requested one.
 * No routing: run() skips it in the console.
 */
Lifecycle::context('cli', PHP_SAPI === 'cli', function () {
    Terminal::addCommand(App\Console\Serve::class);
    Terminal::addCommand(Expansa\Assets\Commands\Clean::class);
    Terminal::addCommand(Expansa\Hooks\Commands\Index::class);
    Terminal::addCommand(new Expansa\Scheduler\Commands\Run(
        schedule: fn (Expansa\Scheduler\Scheduler $scheduler) => Hook::call('schedule', $scheduler),
    ));
    Terminal::addCommand(new Expansa\Ai\Commands\Work(
        queue: fn () => App\Support\Ai::queue(),
    ));

    // the site-health page tells from the time of this mark whether cron runs the scheduler
    Hook::add('schedule', fn () => App\Support\SiteHealth::markScheduler());

    // AI tasks whose worker did not start or crashed; a request starts its own worker at once
    Hook::add('schedule', function (Expansa\Scheduler\Scheduler $scheduler) {
        $scheduler->raw(PHP_BINARY, [EX_PATH . 'artisan', 'ai:work'])->everyMinute()->onlyOne();
    });

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

    // RequireAuth has its own allow-list for installation, account setup, password recovery and passkey sign-in.
    Route::before('*', '/api/.*', [App\Http\RequireAuth::class, 'handle']);

    // routes
    Route::prefix('/api', function () {
        // reference implementation: routes derived by Router::register() (POST /system/test, POST /system/install)
        Route::register(App\Api\System\SystemController::class, [App\Http\Kernel::class, 'dispatch']);

        // RPC routes instead of Router::register(): the dashboard calls fixed URLs like `apikey/create` with the id in the body
        foreach (
            [
                App\Api\Ai\AiController::class,
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
Lifecycle::context('install', fn () => ! Is::installed(), function () {
    require_once EX_PATH . 'dashboard/install.php';

    Route::get('/(.*)', [App\Controllers\Web::class, 'install']);
});

/**
 * 4. auth · sign-in, sign-up and reset-password pages.
 *
 * A logged-in user goes straight to the dashboard. Otherwise, dashboard/auth.php enqueues
 * only the assets the auth forms need, without the admin panel.
 */
Lifecycle::context('sign-out', function (string $uri): bool {
    $uri  = trim($uri, '/');
    $root = trim((string) Hook::call('dashboardRootSlug', 'dashboard'), '/');

    return $uri === 'sign-out' || $uri === "$root/sign-out";
}, function () {
    // the next signed-in account of the browser takes over, ?all=1 signs out every one
    $user = App\Models\User::current();
    if ($user !== null) {
        App\Api\User\Events::record($user, 'sign_out', isset($_GET['all']) ? ['all' => true] : []);
    }

    Auth::logout(all: isset($_GET['all']));
    redirect(Auth::isLoggedIn() ? 'dashboard' : 'sign-in');
});

// links of account emails: the email confirmation and "this wasn't me" of a new device sign-in
Lifecycle::context('account-links', fn (string $uri) => in_array(trim($uri, '/'), ['verify-email', 'secure-account', 'sign-in-link'], true), function () {
    Route::get('/verify-email', [App\Controllers\Account::class, 'verifyEmail']);
    Route::get('/sign-in-link', [App\Controllers\Account::class, 'signInLink']);
    Route::get('/secure-account', [App\Controllers\Account::class, 'secure']);
});

// /oauth/<name> leaves for a provider of the Security settings, /oauth/<name>/callback signs in or connects it, see App\Controllers\OAuth
Lifecycle::context('oauth', fn (string $uri) => str_starts_with(trim($uri, '/'), 'oauth/'), function () {
    Route::get('/oauth/([a-z0-9-]+)', [App\Controllers\OAuth::class, 'redirect']);
    Route::get('/oauth/([a-z0-9-]+)/callback', [App\Controllers\OAuth::class, 'callback']);
});

Lifecycle::context('auth', fn (string $uri) => in_array(trim($uri, '/'), ['sign-in', 'sign-up', 'reset-password'], true), function () {
    // ?add=1 signs in to one more account, the current one stays switchable in the user menu
    if (Auth::isLoggedIn() && ! isset($_GET['add'])) {
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
    // back to the requested page after signing in, like WordPress' redirect_to
    if (! Auth::isLoggedIn()) {
        redirect('sign-in?redirect_to=' . rawurlencode((string) ($_SERVER['REQUEST_URI'] ?? '')));
    }

    // a role that requires two-factor authentication sees only the profile until it is set up
    $user = App\Models\User::current();
    $isProfile = str_contains((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/profile');
    if (! $isProfile && App\Api\User\TwoFactor::isRequired($user) && ! App\Api\User\TwoFactor::isEnabled($user)) {
        redirect(Hook::call('dashboardRootSlug', 'dashboard') . '/profile');
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
 * Errors from any step go to the log and the error page, see Debug::configure() above.
 */
Lifecycle::run(catch: function (Throwable $e) {
    // a bug in a plugin fails this request only: the plugin is skipped from the next one
    Extensions::quarantine($e);

    Debug::handle($e);
});
