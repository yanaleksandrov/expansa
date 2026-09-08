<?php

declare(strict_types=1);

namespace App\Http;

use Expansa\Http\Exceptions\HttpException;
use Expansa\Http\Exceptions\ValidationException;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Is;
use Throwable;

/**
 * Central dispatch point for API controllers.
 *
 * The router only invokes a route handler and discards whatever it returns
 * (see Expansa\Routing\Router::invoke()) — a controller that just `return`s
 * an array never actually sends anything to the client. Kernel::dispatch()
 * is the one place that builds the Request, calls the controller, and turns
 * either its return value or a thrown exception into a single consistent
 * JSON envelope, then sends it:
 *
 *   success       -> { "data": <return value> }
 *   HttpException -> { "message": ..., "errors"?: ... }  with the exception's status code
 *   anything else -> { "message": ... }  with status 500
 *
 * In debug mode (EX_DEBUG), every JSON response also carries `benchmark`/`memory`
 * metrics — never in production, so nothing about the server leaks by default.
 *
 * A controller that needs to send something other than JSON (a file download, an
 * HTML fragment) can return an Expansa\Http\Response directly — Kernel sends it
 * as-is, skipping the envelope entirely.
 *
 * Route registration for a controller looks like:
 *   Route::post('/system/test', fn (...$p) => Kernel::dispatch(SystemController::class, 'test', $p));
 */
final class Kernel
{
    public static function dispatch(string $controller, string $method, array $params = []): void
    {
        $request = Request::createFromGlobals();

        try {
            $result = (new $controller())->{$method}($request, ...$params);

            $response = $result instanceof Response
                ? $result
                : (new Response())->json(self::withMetrics(['data' => $result]));
        } catch (HttpException $e) {
            $payload = ['message' => $e->getMessage()];
            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->getErrors();
            }

            $response = (new Response())->json(self::withMetrics($payload), $e->getStatusCode());
        } catch (Throwable $e) {
            $response = (new Response())->json(self::withMetrics([
                'message' => Is::debug() ? $e->getMessage() : t('Something went wrong. Please try again later.'),
            ]), 500);
        }

        $response->prepare($request)->send();
    }

    /**
     * Adds benchmark/memory to a JSON payload in debug mode only — a debugging aid,
     * not something to expose in production.
     */
    private static function withMetrics(array $payload): array
    {
        if (!Is::debug()) {
            return $payload;
        }

        return $payload + [
            'benchmark' => metrics()->time(),
            'memory'    => metrics()->memory(),
        ];
    }
}
