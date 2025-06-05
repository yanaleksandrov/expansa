<?php

declare(strict_types=1);

namespace App\Http;

use Expansa\Security\Csrf\Csrf;
use Expansa\Security\Csrf\Providers\NativeHttpOnlyCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;
use Random\RandomException;

final class VerifyCsrfToken
{
    /**
     * @throws RandomException
     */
    public function handle(): void
    {
        $csrf = new Csrf(new NativeHttpOnlyCookieProvider());
        try {
            $csrf->check('token', $_COOKIE['expansa_token'] ?? '');
        } catch (InvalidCsrfTokenException $e) {
            error('api-no-route', t('Ajax queries not allows without CSRF token!'));
        }

        // generate CSRF token.
        $csrf->generate('token');
    }
}
