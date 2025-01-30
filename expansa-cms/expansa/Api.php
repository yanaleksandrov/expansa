<?php

declare(strict_types=1);

namespace Expansa;

use Exception;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;
use Expansa\Facades\Json;
use Expansa\Facades\Route;
use Expansa\Facades\Safe;
use Expansa\Security\Csrf\Csrf;
use Expansa\Security\Csrf\Providers\NativeHttpOnlyCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;
use ReflectionException;

final class Api
{
    public static array $resources = [];

    /**
     * Register new API.
     * TODO: в качестве переменной $path добавить возможность указывать путь не только к папке, но и к файлу.
     * Это позволит при необходимости подключать эндпоинты выборочно, а не пакетно.
     *
     * @param string $root Root of the API.
     * @param string $dirpath
     * @throws Exception
     */
    public static function configure(string $root, string $dirpath): void
    {
        self::scan($dirpath);
        echo '<pre>';
        print_r(self::$resources);
        echo '</pre>';

        Route::middleware($root, function () {
            Route::get('/(.*)', fn ($slug) => self::run($slug));
            Route::post('/(.*)', fn ($slug) => self::run($slug));
        });
        Route::run();
    }

    private static function run($slug): void
    {
        [$endpoint, $method] = explode('/', $slug, 2) + [null, null];

        $resource = self::$resources[$endpoint] ?? '';
        if (empty($resource)) {
            // TODO: return error
        }

        $method   = Safe::camelcase($method);
        $class    = Safe::pascalcase($resource['class'] ?? '');
        $filepath = Safe::path($resource['filepath'] ?? '');

        require_once $filepath;

        $csrf = new Csrf(new NativeHttpOnlyCookieProvider());
        try {
            $csrf->check('token', $_COOKIE['expansa_token'] ?? '');
        } catch (InvalidCsrfTokenException $e) {
            $data = new Error('api-no-route', t('Ajax queries not allows without CSRF token!'));
        }

        if (empty($data)) {
            $csrf->generate('token');

            try {
                $reflector   = new \ReflectionClass($class);
                $classMethod = $reflector->getMethod($method);
                if ($classMethod->isStatic()) {
                    $data = $class::$method();
                } else {
                    $data = ( new $class() )->{$method}();
                }
            } catch (ReflectionException $e) {
                $data = new Error('api-no-route', t('No route was found matching the URL and request method.'));
            }

            /**
             * Interceptor for overriding the server response.
             *
             * @since 2025.1
             */
            $data = Hook::call('expansa_api_response', $data, $slug);
        }

        $data = Json::encode(
            [
                'status'    => 200,
                'benchmark' => metrics()->time(),
                'memory'    => metrics()->memory(),
                'queries'   => count(Db::log()),
                'data'      => $data instanceof Error ? [] : $data,
                'errors'    => $data instanceof Error ? Error::get() : [],
            ],
            true,
            true
        );

        header('Content-Type: application/json; charset=utf-8');
        exit($data);
    }

    /**
     * Extract all API classes.
     *
     * @param string $path Path to directory with API controllers.
     * @throws Exception
     */
    private static function scan(string $path): void
    {
        if (! is_dir($path)) {
            throw new Exception(t('API path is not a directory'));
        }

        $files = array_diff(scandir($path), [ '.', '..' ]);

        foreach ($files as $file) {
            $filepath = Safe::path($path . DIRECTORY_SEPARATOR . $file);

            if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && is_readable($filepath)) {
                $content = file_get_contents($filepath);

                $namespace = preg_match('/namespace\s+([^\s;]+)/', $content, $matches) ? $matches[1] : '';
                $class     = preg_match('/class\s+(\S+)/', $content, $matches) ? $matches[1] : '';
                $endpoint  = preg_match('/\$endpoint\s*=\s*\'([^\']+)\'/', $content, $matches) ? $matches[1] : '';

                if (! empty($class) && ! empty($endpoint)) {
                    self::$resources[ $endpoint ] = [
                        'namespace' => $namespace ?? '',
                        'class'     => $namespace ? $namespace . '\\' . $class : $class,
                        'filepath'  => $filepath,
                    ];
                }
            } elseif (is_dir($filepath)) {
                self::scan($filepath);
            }
        }
    }
}
