<?php

declare(strict_types=1);

namespace App\Http;

use Expansa\Access\Exceptions\AccessDenied;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Access;
use Expansa\Facades\Auth;
use Expansa\Facades\Cookie;
use Expansa\Facades\Debug;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Exceptions\ResponseReady;
use Expansa\Http\Exceptions\ValidationFailed;
use Expansa\Http\Request;
use Expansa\Http\Response;
use Expansa\Support\Is;
use ReflectionMethod;
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
 *   HttpError -> { "message": ..., "errors"?: ... }  with the exception's status code
 *   AccessDenied  -> { "message": ... }  with status 403
 *   TooManyAttempts -> a notice fragment with the time to wait
 *   ResponseReady -> the exception's response as-is
 *
 * Cookies queued with the Cookie facade are added to every response.
 *   anything else -> { "message": ... }  with status 500 and the error id, reported by Debug::report()
 *
 * In debug mode (EX_DEBUG['enabled']), every JSON response also carries `benchmark`/`memory`
 * metrics — never in production, so nothing about the server leaks by default.
 *
 * A controller that needs to send something other than JSON (a file download, an
 * HTML fragment) can return an Expansa\Http\Response directly — Kernel sends it
 * as-is, skipping the envelope entirely. A Response with fragments (notify(), remove(), ...)
 * is the `{ "data": [...] }` envelope already and only gets the debug metrics.
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
            $permissions = (new ReflectionMethod($controller, $method))->getAttributes(Can::class);

            // an API token reaches only the endpoints that name a permission of its scopes
            if ($permissions === [] && Auth::isBearer()) {
                throw new AccessDenied($method);
            }

            foreach ($permissions as $attribute) {
                Access::authorize(Auth::user(), $attribute->newInstance()->permission);
            }

            $result = new $controller()->{$method}($request, ...$params);

            $response = $result instanceof Response
                ? $result
                : new Response()->json(self::withMetrics(['data' => $result]));
        } catch (ResponseReady $e) {
            $response = $e->response;
        } catch (AccessDenied) {
            $response = new Response()->json(self::withMetrics(['message' => t('You are not allowed to do this.')]), 403);
        } catch (TooManyAttempts $e) {
            // a notice fragment: the dashboard shows fragments of successful answers only
            $message  = t('Too many attempts. Try again in :minutes min.', (int) ceil($e->retryAfter / 60));
            $response = response()->notify($message);
        } catch (HttpError $e) {
            $payload = ['message' => $e->getMessage()];
            if ($e instanceof ValidationFailed) {
                $payload['errors'] = $e->errors;
            }

            $response = new Response()->json(self::withMetrics($payload), $e->statusCode);
        } catch (Throwable $e) {
            // the id is in the log and the response, to find one by the other
            $id = Debug::report($e, ['controller' => $controller, 'method' => $method]);

            $response = new Response()->json(self::withMetrics([
                'message' => Debug::hasDetails() ? $e->getMessage() : t('Something went wrong. Please try again later.'),
                'id'      => $id,
            ]), 500);
        }

        // fragments get the same metrics as the other JSON answers
        if ($response->fragments !== []) {
            $response->json(self::withMetrics(['data' => $response->fragments]), $response->statusCode);
        }

        foreach (Cookie::getQueue() as $cookie) {
            $response->setCookie($cookie);
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
