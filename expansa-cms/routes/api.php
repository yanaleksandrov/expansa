<?php

declare(strict_types=1);

use App\Api\Apikey\ApikeyController;
use App\Api\Extensions\ExtensionsController;
use App\Api\Files\FilesController;
use App\Api\Media\MediaController;
use App\Api\Options\OptionsController;
use App\Api\Post\PostController;
use App\Api\Posts\PostsController;
use App\Api\System\SystemController;
use App\Api\Translations\TranslationsController;
use App\Api\User\UserController;
use App\Http\Kernel;
use App\Http\RequireAuth;
use App\Http\VerifyCsrfToken;
use Expansa\Facades\Safe;
use Expansa\Facades\Route;

Route::before('*', '/api/.*', function () {
    header('Content-Type: application/json; charset=utf-8');
});

// Only mutating requests need a CSRF token — safe methods (GET) are exempt by convention.
Route::before('POST|PUT|PATCH|DELETE', '/api/.*', [VerifyCsrfToken::class, 'handle']);

// Everything under /api needs a logged-in session except RequireAuth's own allow-list
// (system/test, system/install, user/sign-in, user/sign-up, user/reset-password).
Route::before('*', '/api/.*', [RequireAuth::class, 'handle']);

Route::prefix('/api', function () {
    /**
     * Reference implementation — see App\Api\System for the Controller/Service split,
     * Expansa\Routing\Router::register() for how routes get derived from the controller's
     * methods (test()/install() aren't CRUD names, so both end up as RPC routes: POST
     * /system/test, POST /system/install), and App\Http\Kernel::dispatch — passed in here
     * as the thing that actually turns a match into a request/response — for how a return
     * value becomes a real JSON response.
     */
    Route::register(SystemController::class, [Kernel::class, 'dispatch']);

    /**
     * The rest of the resources, migrated onto the Controller/Service/Kernel pattern but
     * kept on the same reflection-based RPC registration as before (not Router::register()):
     * every one of these controllers has a method literally named create/update/delete,
     * and the dashboard already calls fixed URLs like `apikey/create` or `user/update`
     * with any target id in the request body — Router::register()'s CRUD convention would
     * remap those to REST verbs (POST /apikey, PUT /user/{id}, ...) that don't match what
     * the frontend actually sends. So: same URL shape as the old legacy loop, dispatched
     * through Kernel instead of being invoked (and returned-from) directly.
     */
    foreach (
        [
            ApikeyController::class,
            ExtensionsController::class,
            FilesController::class,
            MediaController::class,
            OptionsController::class,
            PostController::class,
            PostsController::class,
            TranslationsController::class,
            UserController::class,
        ] as $class
    ) {
        foreach (get_class_methods($class) as $method) {
            if ($method === '__construct') {
                continue;
            }

            $classname = new ReflectionClass($class)->getShortName();
            $prefix    = Safe::lowercase(preg_replace('/Controller$/', '', $classname));
            $endpoint  = Safe::kebabcase($method);

            Route::post("/$prefix/$endpoint", fn (...$params) => Kernel::dispatch($class, $method, $params));
        }
    }
});
