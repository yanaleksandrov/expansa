<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Facades\Db;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\View;
use Throwable;

/**
 * Warning about a sign-in from a new device, with a "this wasn't me" link: it signs the account
 * out everywhere and sends a password reset. The link is signed with the password hash, so it
 * stops working once the password changes, and lives a week.
 */
final class Security
{
    /**
     * Lifetime of the "this wasn't me" link in seconds.
     */
    private const int LINK_TTL = 604800;

    /**
     * Email the owner about a sign-in from a browser that hasn't signed in to the account before.
     *
     * @param User   $user
     * @param string $method `password`, `passkey`, a provider name.
     * @return void
     */
    public static function notifyNewDevice(User $user, string $method): void
    {
        try {
            $expires = time() + self::LINK_TTL;
            $body    = View::create('mails/wrapper', [
                'body_template' => 'mails/new-device',
                'name'          => $user->showname ?: $user->login,
                'device'        => Sessions::describe((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
                'ip'            => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'time'          => date('Y-m-d H:i T'),
                'method'        => $method,
                'secureUrl'     => url('secure-account?' . http_build_query([
                    'user'      => $user->id,
                    'expires'   => $expires,
                    'signature' => self::sign($user, $expires),
                ])),
            ])->render();

            $message = Mail::to($user->email)->subject(t('New sign-in to your account'))->message($body);
            if (! $message->send()) {
                Log::error('New device: the email was not sent.', ['user' => $user->id, 'error' => $message->error]);
            }
        } catch (Throwable $error) {
            // the sign-in itself must not fail because of the mail
            Log::error('New device: the email failed.', ['user' => $user->id, 'error' => $error->getMessage()]);
        }
    }

    /**
     * Handle the "this wasn't me" link: sign out every device and send a password reset.
     *
     * @param int    $userId
     * @param int    $expires
     * @param string $signature
     * @return bool False for an invalid or expired link.
     */
    public static function secure(int $userId, int $expires, string $signature): bool
    {
        $user = User::find($userId);
        if (! $user instanceof User || $expires < time() || ! hash_equals(self::sign($user, $expires), $signature)) {
            return false;
        }

        Db::delete('user_sessions', ['user_id' => $user->id]);
        Events::record($user, 'account_secured');
        new UserService()->requestPasswordReset($user);

        return true;
    }

    /**
     * Signature of a link: bound to the user, the expiry and the password hash.
     *
     * @param User $user
     * @param int  $expires
     * @return string
     */
    private static function sign(User $user, int $expires): string
    {
        return hash_hmac('sha256', "secure|$user->id|$expires|$user->password", defined('EX_KEYS') ? EX_KEYS['auth'] : '');
    }
}
