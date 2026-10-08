<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Api\User\Security;
use App\Api\User\Verification;

/**
 * Links of account emails: the email confirmation after signing up and "this wasn't me" of
 * the new device warning. Both end on the sign-in page with a notice of the result.
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
