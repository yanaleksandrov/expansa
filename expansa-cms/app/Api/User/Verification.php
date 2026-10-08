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
 * Email confirmation of accounts that signed up themselves: a link with a one-day token,
 * the table keeps only its hash. Until it is opened the account can't sign in.
 */
final class Verification
{
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

        $body = View::create('mails/wrapper', [
            'body_template' => 'mails/verify-email',
            'name'          => $user->showname ?: $user->login,
            'verifyUrl'     => url('verify-email?token=' . $token),
        ])->render();

        $message = Mail::to($user->email)->subject(t('Confirm your email'))->message($body);
        if (! $message->send()) {
            Log::error('Email confirmation: the email was not sent.', ['user' => $user->id, 'error' => $message->error]);
        }
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

        $user->update([
            'is_verified'                   => true,
            'verification_token'            => null,
            'verification_token_expires_at' => null,
        ]);
        Events::record($user, 'email_verified');

        return $user;
    }
}
