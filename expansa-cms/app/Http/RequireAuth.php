<?php

declare(strict_types=1);

namespace App\Http;

use Expansa\Facades\Auth;
use Expansa\Facades\Route;
use Expansa\Http\Response;

/**
 * Gates /api/* behind a logged-in session. CSRF alone only proves a request came from
 * this origin — it says nothing about who's making it. Before this, an unauthenticated
 * visitor with nothing more than a page load (which is all it takes to get a CSRF
 * cookie — see VerifyCsrfToken::seed()) could call any mutating API endpoint.
 *
 * A short, explicit allow-list covers the handful of routes that must work for someone
 * who, by definition, isn't logged in yet.
 */
final class RequireAuth
{
    private const array PUBLIC_ROUTES = [
        '/api/system/test',
        '/api/system/install',
        '/api/user/sign-in',
        '/api/user/sign-up',
        '/api/user/reset-password',
        '/api/user/passkey-options',
        '/api/user/passkey-sign-in',
        '/api/user/two-factor',
        '/api/user/email-link',
    ];

    public function handle(): void
    {
        if (in_array(Route::uri(), self::PUBLIC_ROUTES, true)) {
            return;
        }

        if (Auth::isLoggedIn()) {
            return;
        }

        new Response()
            ->json(['message' => t('Authentication required')], 401)
            ->send();
        exit;
    }
}
