<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Api\User\EmailLink;
use App\Api\User\Security;
use App\Api\User\SignIn;
use App\Api\User\Verification;
use Expansa\Http\Redirect;

/**
 * Links of account emails: the email confirmation, "this wasn't me" of the new device warning
 * and the sign-in link. They end on the sign-in page with a notice, or signed in.
 */
final class Account
{
    /**
     * Confirm the email by the link token.
     *
     * @return void
     */
    public function verifyEmail(): void
    {
        $user = Verification::verify((string) ($_GET['token'] ?? ''));

        redirect('sign-in?notice=' . ($user !== null ? 'email-verified' : 'link-invalid'));
    }

    /**
     * Sign in by a link of the email; two-factor authentication still asks for its code.
     *
     * @return void
     */
    public function signInLink(): void
    {
        $link = EmailLink::use((string) ($_GET['token'] ?? ''));
        if ($link === null) {
            redirect('sign-in?notice=link-invalid');
        }

        [$user, $redirectTo] = $link;
        if (SignIn::refusal($user) !== null) {
            redirect('sign-in?error=oauth-refused');
        }

        Redirect::send(SignIn::finish($user, false, 'email-link', $redirectTo));
    }

    /**
     * Sign out every device of the account and send a password reset.
     *
     * @return void
     */
    public function secure(): void
    {
        $userId    = (int) ($_GET['user'] ?? 0);
        $isSecured = Security::secure($userId, (int) ($_GET['expires'] ?? 0), (string) ($_GET['signature'] ?? ''));

        redirect('sign-in?notice=' . ($isSecured ? 'account-secured' : 'link-invalid'));
    }
}
