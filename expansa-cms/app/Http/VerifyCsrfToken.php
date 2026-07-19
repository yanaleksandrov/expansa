<?php

declare(strict_types=1);

namespace App\Http;

use Expansa\Security\Csrf\Csrf;
use Expansa\Security\Csrf\Providers\NativeHttpOnlyCookieProvider;
use Expansa\Security\Exception\InvalidCsrfTokenException;
use Random\RandomException;

/**
 * Class VerifyCsrfToken
 *
 * Handles CSRF token verification for incoming requests.
 * If the token is missing or invalid, it triggers an error response.
 * After verification, a new CSRF token is generated.
 */
final class VerifyCsrfToken
{
    /**
     * Verify the CSRF token from the request.
     *
     * This method checks the token from the 'expansa_token' cookie against
     * the expected CSRF token. If the token is invalid or missing, an error is raised.
     * Afterwards, it generates a new CSRF token for future requests.
     *
     * @throws RandomException If token generation fails
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
