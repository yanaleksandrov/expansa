<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\Option;
use App\Models\User;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Cache\Providers\Database as DatabaseCache;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\View;

/**
 * Sign-in by a link sent to the email, when the Security settings allow it: a one-time link that
 * lives TTL seconds. The token is kept only as a hash in the `cache` table. Two-factor
 * authentication still asks for its code after the link.
 */
final class EmailLink
{
    /**
     * Seconds a link lives.
     */
    private const int TTL = 900;

    /**
     * Cache group of the links.
     */
    private const string GROUP = 'email-links';

    /**
     * Whether the Security settings allow signing in by an email link.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return (bool) Option::get('security.email_link', false);
    }

    /**
     * Send a sign-in link to an email if it belongs to an active account; at most three in an hour per account.
     *
     * @param string $email
     * @param string $redirectTo Page to go to after signing in, see SignIn::target().
     * @return void
     */
    public static function send(string $email, string $redirectTo = ''): void
    {
        $user = $email === '' ? null : User::find($email, 'email');
        if (! $user instanceof User || $user->status !== User::STATUS_ACTIVE) {
            return;
        }

        try {
            Auth::limit("email-link:$user->id", 3, 3600);
        } catch (TooManyAttempts) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $link  = ['user' => $user->id, 'redirect_to' => $redirectTo];
        self::store()->add(hash('sha256', $token), $link, self::GROUP, '+' . self::TTL . ' seconds');

        $body = View::create('mails/wrapper', [
            'body_template' => 'mails/sign-in-link',
            'name'          => $user->showname ?: $user->login,
            'signInUrl'     => url('sign-in-link?token=' . $token),
        ])->render();

        $message = Mail::to($user->email)->subject(t('Your sign-in link'))->message($body);
        if (! $message->send()) {
            Log::error('Email link: the email was not sent.', ['user' => $user->id, 'error' => $message->error]);
        }
    }

    /**
     * Use up a link.
     *
     * @param string $token Raw token from the link.
     * @return array{User, string}|null The user and the page to go to, null for an invalid, used or expired link.
     */
    public static function use(string $token): ?array
    {
        if (! self::isEnabled() || ! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $link = self::store()->pull(hash('sha256', $token), self::GROUP);
        $user = is_array($link) ? User::find((int) ($link['user'] ?? 0)) : null;

        return $user instanceof User ? [$user, (string) ($link['redirect_to'] ?? '')] : null;
    }

    /**
     * The links live in the `cache` table: the default cache store may be the request memory.
     *
     * @return DatabaseCache
     */
    private static function store(): DatabaseCache
    {
        return new DatabaseCache(Db::instance());
    }
}
