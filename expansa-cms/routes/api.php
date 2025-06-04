<?php

declare(strict_types=1);

use Expansa\Facades\Safe;
use Expansa\Facades\Route;
use Expansa\Security\Csrf\Csrf;
use Expansa\Security\Csrf\Providers\NativeHttpOnlyCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;

Route::before('GET|POST', '/api/.*', function () {
    header('Content-Type: application/json; charset=utf-8');

    $csrf = new Csrf(new NativeHttpOnlyCookieProvider());
    try {
        $csrf->check('token', $_COOKIE['expansa_token'] ?? '');
    } catch (InvalidCsrfTokenException $e) {
        $data = error('api-no-route', t('Ajax queries not allows without CSRF token!'));
    }

    // generate CSRF token.
    $csrf->generate('token');
});

Route::middleware('/api', function () {
    foreach (
        [
            App\Api\Extensions::class,
            App\Api\Files::class,
            App\Api\Media::class,
            App\Api\Option::class,
            App\Api\Post::class,
            App\Api\Posts::class,
            App\Api\System::class,
            App\Api\Translations::class,
            App\Api\User::class,
        ] as $class
    ) {
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            $classname = (new ReflectionClass($class))->getShortName();
            $prefix    = Safe::lowercase($classname);
            $endpoint  = Safe::kebabcase($method);

            Route::post("/$prefix/$endpoint", [$class, $method]);
        }
    }
});

///**
// * Launch routing.
// *
// * @since 2025.1
// */
//Route::run();
//
//exit;
