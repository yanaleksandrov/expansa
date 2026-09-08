<?php

declare(strict_types=1);

use App\Api\System\SystemController;
use App\Http\Kernel;
use App\Http\VerifyCsrfToken;
use Expansa\Facades\Safe;
use Expansa\Facades\Route;

Route::before('*', '/api/.*', function () {
    header('Content-Type: application/json; charset=utf-8');
});

// Only mutating requests need a CSRF token — safe methods (GET) are exempt by convention.
Route::before('POST|PUT|PATCH|DELETE', '/api/.*', [VerifyCsrfToken::class, 'handle']);

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
     * Legacy endpoints, still auto-registered via reflection and dispatched directly by the
     * router (their `return` values are discarded — see Expansa\Routing\Router::invoke()).
     * To be migrated one by one onto the Controller/Service/Kernel pattern above.
     */
    foreach (
        [
            App\Api\Apikey::class,
            App\Api\Extensions::class,
            App\Api\Files::class,
            App\Api\Media::class,
            App\Api\Options::class,
            App\Api\Post::class,
            App\Api\Posts::class,
            App\Api\Translations::class,
            App\Api\User::class,
        ] as $class
    ) {
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            $classname = new ReflectionClass($class)->getShortName();
            $prefix    = Safe::lowercase($classname);
            $endpoint  = Safe::kebabcase($method);

            Route::post("/$prefix/$endpoint", [$class, $method]);
        }
    }
});
