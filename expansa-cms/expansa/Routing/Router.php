<?php

declare(strict_types=1);

namespace Expansa\Routing;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Class Route.
 */
class Router
{
    /**
     * The function to be executed when no route has been matched
     *
     * @var array [object|callable]
     */
    private array $notFoundCallback = [];

    /**
     * Literal (no `{param}`, no raw regex) route patterns, keyed by method then by the
     * exact pattern string, for an O(1) lookup instead of a linear regex scan. Most
     * routes in practice are literal (`/system/test`, `/post/create`, ...).
     *
     * @var array
     */
    private array $staticRoutes = [];

    /**
     * Genuinely dynamic route patterns (`{param}` or a hand-written regex like `/(.*)`),
     * keyed by method, then by their literal prefix — the part of the pattern before the
     * first placeholder/regex character (`/post/{id}` -> `/post`; a prefix-less pattern
     * like `/(.*)` buckets under `''`). Dispatch only has to scan the bucket matching the
     * request's own prefix plus the `''` (catch-all) bucket, instead of every dynamic
     * route in the app — see handleDynamic(). Each entry also carries a `seq` so merging
     * those two buckets can still be scanned in original registration order.
     *
     * @var array
     */
    private array $dynamicRoutes = [];

    /**
     * Registration order counter — lets handleDynamic() restore first-registered-wins
     * order after merging a prefix bucket with the catch-all bucket.
     *
     * @var int
     */
    private int $routeSequence = 0;

    /**
     * The before middleware route patterns and their handling functions
     *
     * @var array
     */
    private array $beforeRoutes = [];

    /**
     * Current base route, used for (sub)route mounting
     *
     * @var string
     */
    private string $baseRoute = '';

    /**
     * The Server Base Path for Router Execution
     *
     * @var string
     */
    private string $serverBasePath = '';

    /**
     * Default Controllers Namespace
     *
     * @var string
     */
    private string $namespace = '';

    /**
     * Store a before middleware route and a handling function to be
     * executed when accessed using one of the specified methods.
     *
     * @param string         $methods Allowed methods, | delimited
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function before(string $methods, string $pattern, callable|array $fn): void
    {
        $pattern = $this->baseRoute . '/' . trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

        if ($methods === '*') {
            $methods = 'GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD';
        }

        foreach (explode('|', $methods) as $method) {
            $this->beforeRoutes[$method][] = [
                'pattern' => $pattern,
                'fn'      => $fn,
            ];
        }
    }

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods.
     *
     * @param string         $methods Allowed methods, | delimited
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function match(string $methods, string $pattern, callable|array $fn): void
    {
        $pattern = $this->baseRoute . '/' . trim($pattern, '/');
        $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

        if ($this->isLiteralPattern($pattern)) {
            foreach (explode('|', $methods) as $method) {
                $this->staticRoutes[$method][$pattern] = $fn;
            }
            return;
        }

        $route = [
            'pattern' => $pattern,
            'regex'   => $this->compileRegex($pattern),
            'fn'      => $fn,
            'seq'     => $this->routeSequence++,
        ];
        $prefix = $this->literalPrefix($pattern);

        foreach (explode('|', $methods) as $method) {
            $this->dynamicRoutes[$method][$prefix][] = $route;
        }
    }

    /**
     * Shorthand for a route accessed using any method.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function any(string $pattern, callable|array $fn): void
    {
        $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using GET.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function get(string $pattern, callable|array $fn): void
    {
        $this->match('GET', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using POST.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function post(string $pattern, callable|array $fn): void
    {
        $this->match('POST', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using PATCH.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function patch(string $pattern, callable|array $fn): void
    {
        $this->match('PATCH', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using DELETE.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function delete(string $pattern, callable|array $fn): void
    {
        $this->match('DELETE', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using PUT.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function put(string $pattern, callable|array $fn): void
    {
        $this->match('PUT', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using OPTIONS.
     *
     * @param string         $pattern A route pattern such as /about/system
     * @param callable|array $fn      The handling function to be executed
     */
    public function options(string $pattern, callable|array $fn): void
    {
        $this->match('OPTIONS', $pattern, $fn);
    }

    /**
     * Reflects a Controller class and registers a route per public method.
     *
     * A handful of method names are recognised as CRUD (index/show/create/update/delete)
     * and get real REST verbs, with `/{id}` appended where a single item is addressed.
     * Anything else becomes a named RPC-style action: `POST /{resource}/{kebab-method}`.
     *
     * This class only handles the naming/verb convention — it doesn't call the controller
     * itself. That's the $dispatch callback's job: it receives ($controller, $method, $params)
     * for whichever route matched, and decides what actually happens (building the request,
     * turning the return value or a thrown exception into a response, etc).
     *
     * @param string      $controller Fully qualified Controller class name.
     * @param callable    $dispatch   Called as $dispatch($controller, $method, $params) on match.
     * @param string|null $name       Resource path segment; derived from the class name
     *                                (SystemController -> system) when omitted.
     */
    public function register(string $controller, callable $dispatch, ?string $name = null): void
    {
        $crud = [
            'index'  => ['GET', ''],
            'show'   => ['GET', '/{id}'],
            'create' => ['POST', ''],
            'update' => ['PUT', '/{id}'],
            'delete' => ['DELETE', '/{id}'],
        ];

        $shortName = new ReflectionClass($controller)->getShortName();
        $resource  = $name ?? strtolower(preg_replace('/Controller$/', '', $shortName));

        foreach (get_class_methods($controller) as $method) {
            if ($method === '__construct') {
                continue;
            }

            [$verb, $suffix] = $crud[$method] ?? ['POST', '/' . $this->kebabCase($method)];

            $this->match($verb, "/$resource$suffix", fn (...$params) => $dispatch($controller, $method, $params));
        }
    }

    /**
     * Converts a camelCase method name into a kebab-case URL segment (signIn -> sign-in).
     */
    private function kebabCase(string $value): string
    {
        $value = preg_replace('/_/', '-', trim($value));
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1-', $value);

        return strtolower($value);
    }

    /**
     * Mounts a collection of callbacks onto a base route.
     *
     * @param string   $baseRoute The route sub pattern to mount the callbacks on
     * @param callable $fn        The callback method
     */
    public function prefix(string $baseRoute, callable $fn): void
    {
        // Track current base route
        $curBaseRoute = $this->baseRoute;

        // Build new base route string
        $this->baseRoute .= $baseRoute;

        // Call the callable
        call_user_func($fn);

        // Restore original base route
        $this->baseRoute = $curBaseRoute;
    }

    /**
     * Get all request headers.
     *
     * @return array The request headers
     */
    public function getRequestHeaders(): array
    {
        $headers = [];

        // If getallheaders() is available, use that
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            // getallheaders() can return false if something went wrong
            if ($headers !== false) {
                return $headers;
            }
        }

        // Method getallheaders() not available or went wrong: manually extract 'm
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_') || $name === 'CONTENT_TYPE' || $name === 'CONTENT_LENGTH') {
                $formattedName = str_replace(
                    [' ', 'Http'],
                    ['-', 'HTTP'],
                    ucwords(
                        strtolower(
                            str_replace('_', ' ', substr($name, 5))
                        )
                    )
                );

                $headers[$formattedName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get the request method used, taking overrides into account.
     *
     * @return string The Request method to handle
     */
    public function getRequestMethod(): string
    {
        // Take the method as found in $_SERVER
        $method = $_SERVER['REQUEST_METHOD'];

        // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
        // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
            ob_start();
            $method = 'GET';
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getRequestHeaders();

            $methodName = $headers['X-HTTP-Method-Override'] ?? '';
            if (in_array($methodName, ['PUT', 'DELETE', 'PATCH'])) {
                $method = $methodName;
            }
        }

        return $method;
    }

    /**
     * Set a Default Lookup Namespace for Callable methods.
     *
     * @param string $namespace A given namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Get the given Namespace before.
     *
     * @return string The given Namespace if exists
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Execute the router: Loop all defined before middlewares and routes,
     * and execute the handling function if a match was found.
     *
     * @param null|callable|object $callback Function to run after route handling (post-middleware).
     * @return bool
     */
    public function run(callable|object $callback = null): bool
    {
        // Define which method we need to handle
        $requestedMethod = $this->getRequestMethod();

        // Handle all before middlewares
        if (isset($this->beforeRoutes[$requestedMethod])) {
            $this->handle($this->beforeRoutes[$requestedMethod]);
        }

        // Handle all routes: an O(1) lookup for a literal match; only a genuinely dynamic
        // pattern falls back to handleDynamic(), which itself only scans the bucket that
        // could plausibly match instead of every dynamic route in the app.
        $uri      = $this->getCurrentUri();
        $staticFn = $this->staticRoutes[$requestedMethod][$uri] ?? null;

        if ($staticFn !== null) {
            $this->invoke($staticFn);
            $numHandled = 1;
        } else {
            $numHandled = $this->handleDynamic($requestedMethod, $uri);
        }

        // If no route was handled, trigger the 404 (if any)
        if ($numHandled === 0) {
            $this->trigger404();
        } elseif ($callback && is_callable($callback)) {
            $callback($this);
        }

        // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            ob_end_clean();
        }

        // Return true if a route was handled, false otherwise
        return $numHandled !== 0;
    }

    /**
     * Set the 404 handling function.
     *
     * @param callable|object|string $matchFn The function to be executed
     * @param null|callable|array    $fn      The function to be executed
     */
    public function set404(callable|object|string $matchFn, callable|array $fn = null): void
    {
        if (! is_null($fn)) {
            $this->notFoundCallback[$matchFn] = $fn;
        } else {
            $this->notFoundCallback['/'] = $matchFn;
        }
    }

    /**
     * Triggers 404 response.
     *
     * @param null|mixed $match
     */
    public function trigger404(mixed $match = null): void
    {
        // Counter to keep track of the number of routes we've handled
        $numHandled = 0;

        // handle 404 pattern
        if (count($this->notFoundCallback) > 0) {
            // loop fallback-routes
            foreach ($this->notFoundCallback as $route_pattern => $route_callable) {
                // matches result
                $matches = [];

                // check if there is a match and get matches as $matches (pointer)
                $isMatch = $this->patternMatches($route_pattern, $this->getCurrentUri(), $matches);

                // is fallback route match?
                if ($isMatch) {
                    $this->invoke($route_callable, $this->extractParams($matches));

                    ++$numHandled;
                }
            }
        }
        if ($numHandled === 0 && isset($this->notFoundCallback['/'])) {
            $this->invoke($this->notFoundCallback['/']);
        } elseif ($numHandled == 0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        }
    }

    /**
     * Define the current relative URI.
     *
     * @return string
     */
    public function getCurrentUri(): string
    {
        // Get the current Request URI and remove the rewrite base path to run the router in a subfolder.
        $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));

        // Don't take query params into account on the URL
        if (str_contains($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }

        // Remove trailing slash + enforce a slash at the start
        return '/' . trim($uri, '/');
    }

    /**
     * Return server base Path, and define it if isn't defined.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        // Check if server base path is defined, if not define it.
        if (empty($this->serverBasePath)) {
            $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
        }
        return $this->serverBasePath;
    }

    /**
     * Explicit sets the server base path. To be used when your entry script path differs from your entry URLs.
     *
     * @see https://github.com/bramus/router/issues/82#issuecomment-466956078
     * @param string $serverBasePath
     */
    public function setBasePath(string $serverBasePath): void
    {
        $this->serverBasePath = $serverBasePath;
    }

    /**
     * Replace all curly braces matches {} into word patterns (like Laravel)
     * Checks if there is a routing match.
     *
     * @return bool -> is match yes/no
     */
    private function patternMatches($pattern, $uri, &$matches): bool
    {
        $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern);

        // we may have a match!
        return boolval(preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_OFFSET_CAPTURE));
    }

    /**
     * Handle a set of routes: if a match is found, execute the relating handling function.
     *
     * @param array $routes       Collection of route patterns and their handling functions
     * @param bool  $quitAfterRun Does the handle function need to quit after one route was matched?
     * @return int The number of routes handled
     */
    private function handle(array $routes, bool $quitAfterRun = false): int
    {
        // Counter to keep track of the number of routes we've handled
        $numHandled = 0;

        // The current page URL
        $uri = $this->getCurrentUri();

        // Loop all routes
        foreach ($routes as $route) {
            // get routing matches
            $isMatch = $this->patternMatches($route['pattern'], $uri, $matches);

            // is there a valid match?
            if ($isMatch) {
                // Call the handling function with the URL parameters if the desired input is callable
                $this->invoke($route['fn'], $this->extractParams($matches));

                ++$numHandled;

                // If we need to quit, then quit
                if ($quitAfterRun) {
                    break;
                }
            }
        }

        // Return the number of routes handled
        return $numHandled;
    }

    /**
     * Matches the current URI against dynamic (non-literal) routes only — literal ones
     * are already handled by the O(1) lookup in run(). Scans only the bucket sharing the
     * URI's own literal prefix plus the `''` (catch-all) bucket, in original registration
     * order, instead of every dynamic pattern registered for this method.
     */
    private function handleDynamic(string $method, string $uri): int
    {
        $candidates = $this->dynamicRoutes[$method][$this->literalPrefix($uri)] ?? [];
        $catchAll   = $this->dynamicRoutes[$method][''] ?? [];

        if ($candidates && $catchAll) {
            $candidates = array_merge($candidates, $catchAll);

            usort($candidates, fn ($a, $b) => $a['seq'] <=> $b['seq']);
        } elseif ($catchAll) {
            $candidates = $catchAll;
        }

        foreach ($candidates as $route) {
            if (preg_match_all($route['regex'], $uri, $matches, PREG_OFFSET_CAPTURE)) {
                $this->invoke($route['fn'], $this->extractParams($matches));
                return 1;
            }
        }

        return 0;
    }

    /**
     * Turns preg_match_all's PREG_OFFSET_CAPTURE output into the plain param list a
     * route handler receives — same reconstruction handle() has always used.
     */
    private function extractParams(array $matches): array
    {
        $matches = array_slice($matches, 1);

        return array_map(function ($match, $index) use ($matches) {
            if (isset($matches[$index + 1][0][1]) && $matches[$index + 1][0][1] > -1) {
                return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
            }

            return isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
        }, $matches, array_keys($matches));
    }

    private function invoke($fn, $params = []): void
    {
        if (is_callable($fn)) {
            call_user_func_array($fn, $params);
        } else {
            // Explode segments of given route
            [$controller, $method] = $fn;

            // Adjust controller class if namespace has been set
            if ($this->getNamespace() !== '') {
                $controller = $this->getNamespace() . '\\' . $controller;
            }

            try {
                $reflectedMethod = new ReflectionMethod($controller, $method);
                // Make sure it's callable
                if ($reflectedMethod->isPublic() && ! $reflectedMethod->isAbstract()) {
                    if ($reflectedMethod->isStatic()) {
                        forward_static_call_array([$controller, $method], $params);
                    } else {
                        // Make sure we have an instance, because a non-static method must not be called statically
                        if (is_string($controller)) {
                            $controller = new $controller();
                        }
                        call_user_func_array([$controller, $method], $params);
                    }
                }
            } catch (ReflectionException $e) {
                // The controller class is not available or the class does not have the method $method
            }
        }
    }

    /**
     * A pattern is literal when it consists *only* of characters that can never carry
     * regex meaning — letters, digits, `-`, `_`, `/`. Deliberately an allow-list, not a
     * deny-list of "the regex metacharacters I could think of": a route pattern is free
     * to use `{param}` today or any other placeholder/regex syntax tomorrow (`{id:\d+}`,
     * `[0-9]+`, `*`, whatever) — anything not on the allow-list simply falls back to the
     * regex path unchanged instead of being silently (and incorrectly) treated as literal.
     */
    private function isLiteralPattern(string $pattern): bool
    {
        return (bool) preg_match('#^[A-Za-z0-9_/-]*$#', $pattern);
    }

    /**
     * Converts `{param}` placeholders into the regex Router has always matched routes
     * with, and compiles it once at registration instead of on every dispatch.
     */
    private function compileRegex(string $pattern): string
    {
        return '#^' . preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern) . '$#';
    }

    /**
     * The literal path segment a dynamic pattern starts with, e.g. `/post/{id}` -> `/post`.
     * A pattern with no literal lead-in at all (a hand-written `/(.*)`) buckets under `''`
     * — dispatch always checks that bucket too, so it still matches anything it used to.
     */
    private function literalPrefix(string $pattern): string
    {
        return preg_match('#^(/[A-Za-z0-9_-]+)#', $pattern, $m) ? $m[1] : '';
    }
}
