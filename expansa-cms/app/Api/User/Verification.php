<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\View;

/**
 * Email confirmation by a link with a one-day token, the table keeps only its hash: of accounts that
 * signed up themselves (they can't sign in until it is opened) and of a new email of an account
 * (it replaces the old one only when opened, the old address is told about the change).
 */
final class Verification
{
    /**
     * Field of the user the new email waits in until it is confirmed.
     */
    private const string PENDING_EMAIL = 'pending_email';

    /**
     * Lifetime of a confirmation link in seconds.
     */
    private const int TTL = 86400;

    /**
     * Seconds between two links to the same account.
     */
    private const int RESEND_INTERVAL = 300;

    /**
     * Send a new confirmation link, at most once in RESEND_INTERVAL.
     *
     * @param User $user
     * @return void
     */
    public static function send(User $user): void
    {
        try {
            Auth::limit("verify:$user->id", 1, self::RESEND_INTERVAL);
        } catch (TooManyAttempts) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $user->update([
            'verification_token'            => hash('sha256', $token),
            'verification_token_expires_at' => date('Y-m-d H:i:s', time() + self::TTL),
        ]);

        $name = $user->showname ?: $user->login;
        $data = ['name' => $name, 'verifyUrl' => url('verify-email?token=' . $token)];
        self::mail($user->email, t('Confirm your email'), 'mails/verify-email', $data);
    }

    /**
     * Whether the account signed up itself and its email is not confirmed yet; a pending change
     * of the email of an existing account doesn't count.
     *
     * @param User $user
     * @return bool
     */
    public static function isPending(User $user): bool
    {
        return ! $user->isVerified
            && $user->verificationToken !== null
            && (string) $user->field->find(self::PENDING_EMAIL) === '';
    }

    /**
     * Start changing the email: keep the new address pending and send the link to it,
     * tell the old address, so its owner notices a change they did not ask for.
     *
     * @param User   $user
     * @param string $email New address, already checked to be free.
     * @return void
     */
    public static function changeEmail(User $user, string $email): void
    {
        $token = bin2hex(random_bytes(32));
        $user->field->mutate(self::PENDING_EMAIL, $email);
        $user->update([
            'verification_token'            => hash('sha256', $token),
            'verification_token_expires_at' => date('Y-m-d H:i:s', time() + self::TTL),
        ]);

        $name = $user->showname ?: $user->login;
        $data = ['name' => $name, 'verifyUrl' => url('verify-email?token=' . $token)];
        self::mail($email, t('Confirm your new email'), 'mails/verify-email', $data);
        self::mail($user->email, t('Your email is being changed'), 'mails/email-change', ['name' => $name, 'email' => $email]);
    }

    /**
     * Confirm the email by a link token; the token works once.
     *
     * @param string $token Raw token from the link.
     * @return User|null The confirmed user, null for an invalid or expired link.
     */
    public static function verify(string $token): ?User
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $id   = Db::get('users', 'id', ['verification_token' => hash('sha256', $token)]);
        $user = $id ? User::find((int) $id) : null;
        if (! $user instanceof User || ($user->verificationTokenExpiresAt?->getTimestamp() ?? 0) < time()) {
            return null;
        }

        // a link of a new email replaces the address, unless somebody took it meanwhile
        $pending = (string) $user->field->find(self::PENDING_EMAIL);
        if ($pending !== '' && User::find($pending, 'email') instanceof User) {
            return null;
        }

        $user->update([
            'is_verified'                   => true,
            'verification_token'            => null,
            'verification_token_expires_at' => null,
            ...($pending !== '' ? ['email' => $pending] : []),
        ]);
        $user->field->delete(self::PENDING_EMAIL);
        Events::record($user, $pending !== '' ? 'email_changed' : 'email_verified');

        return $user;
    }

    /**
     * Send a mail of the wrapper template; a failure is logged, the change goes on.
     *
     * @param string               $to
     * @param string               $subject
     * @param string               $template Body template, e.g. `mails/verify-email`.
     * @param array<string, mixed> $data
     * @return void
     */
    private static function mail(string $to, string $subject, string $template, array $data): void
    {
        $body    = View::create('mails/wrapper', ['body_template' => $template] + $data)->render();
        $message = Mail::to($to)->subject($subject)->message($body);
        if (! $message->send()) {
            Log::error('Email confirmation: the email was not sent.', ['to' => $to, 'error' => $message->error]);
        }
    }
}
