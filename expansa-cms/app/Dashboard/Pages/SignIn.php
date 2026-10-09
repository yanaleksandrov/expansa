<?php

declare(strict_types=1);

namespace App\Dashboard\Pages;

use App\Api\User\EmailLink;
use App\Api\User\Identities;
use App\Api\User\TwoFactor;
use App\Models\Option;
use Expansa\Facades\Auth;
use Expansa\Http\Request;

/**
 * Sign-in page: the form, the second factor, the OAuth providers and the messages of account links.
 *
 * @package App\Dashboard
 */
final class SignIn
{
    /**
     * Data of the `sign-in` page by the `error`, `notice`, `step`, `add` and `redirect_to` of the address.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public static function data(Request $request): array
    {
        $errors = [
            'oauth-denied'  => t('Sign-in was cancelled.'),
            'oauth-failed'  => t('Could not sign in with this provider. Please try again.'),
            'oauth-email'   => t('An account with this email already exists. Sign in with it and connect the provider in your profile.'),
            'oauth-closed'  => t('Registration is closed or the provider did not confirm your email.'),
            'oauth-limited' => t('Too many attempts. Try again in a few minutes.'),
            'oauth-refused' => t('This account is disabled or its email is not confirmed yet.'),
        ];
        $notices = [
            'email-verified'  => t('Your email is confirmed. You can sign in now.'),
            'account-secured' => t('All devices have been signed out. Check your email to set a new password.'),
            'link-invalid'    => t('This link is invalid or expired.'),
        ];

        $add   = isset($request->query['add']) && Auth::isLoggedIn();
        $query = http_build_query(array_filter(['add' => $add ? 1 : null, 'redirect_to' => $request->getString('redirect_to')]));

        return [
            'error'      => $errors[$request->getString('error')] ?? '',
            'notice'     => $notices[$request->getString('notice')] ?? '',
            'add'        => $add,
            'challenged' => $request->getString('step') === 'two-factor' && TwoFactor::getChallengedUser() !== null,
            'emailLink'  => EmailLink::isEnabled(),
            'membership' => (bool) Option::get('users.membership'),
            'providers'  => array_map(fn (string $provider) => [
                'id'    => $provider,
                'label' => Identities::label($provider),
                'url'   => url("oauth/$provider?$query"),
            ], Auth::getProviders()),
        ];
    }
}
