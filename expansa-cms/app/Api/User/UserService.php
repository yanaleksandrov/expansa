<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Debug\Error;
use Expansa\Facades\Db;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\Safe;
use Expansa\Facades\View;
use Throwable;

final class UserService
{
    /**
     * Lifetime of a password reset link in seconds.
     */
    private const int PASSWORD_RESET_TTL = 3600;

    public function update(array $input): array
    {
        $fields = Safe::data($input, [
            'bio'     => 'trim',
            'toolbar' => 'bool',
            'format'  => 'text',
        ])->apply();

        $user = User::current()->update($input);

        if ($user instanceof User) {
            foreach ($fields as $key => $value) {
                $user->field->update($key, $value);
            }
        }

        return [
            [
                'target' => 'body',
                'notify' => t('User updated.'),
            ],
        ];
    }

    /**
     * Fixed: the old code fell through to also build (and, once wired to a real
     * dispatcher, send) a redirect fragment even after already echoing an error notify
     * on failed login — two responses for one request. Now an early return either way.
     * Also fixed the redirect fragment itself: it used `method`/`fragment` keys, but
     * the frontend's applyFragment() only recognises the action name as the key
     * itself (`redirect`), so this redirect never actually fired.
     */
    public function signIn(array $input): array
    {
        $user = User::login($input);

        if ($user instanceof Error) {
            return [
                [
                    'target' => 'body',
                    'notify' => $user->get('user-login')[0],
                ],
            ];
        }

        return [
            [
                'target'   => 'body',
                'redirect' => url('dashboard'),
            ],
        ];
    }

    /**
     * Start a passkey sign-in: request options without a login, so the response
     * says nothing about which accounts exist or have passkeys.
     *
     * @return array<string, mixed>
     */
    public function passkeyOptions(): array
    {
        return ['options' => new Passkey()->requestOptions()];
    }

    /**
     * Finish a passkey sign-in: verify the assertion and issue the auth cookie.
     *
     * @param array<string, mixed> $input `credential` JSON and the `remember` flag.
     * @return array<int, array<string, mixed>> Redirect or error notice fragments.
     */
    public function passkeySignIn(array $input): array
    {
        try {
            $user = new Passkey()->verify((string) ($input['credential'] ?? ''));
        } catch (Throwable) {
            return [['target' => 'body', 'notify' => t('The passkey could not be verified. Please try again.')]];
        }

        User::authenticate($user, Safe::bool($input['remember'] ?? false));

        return [['target' => 'body', 'redirect' => url('dashboard')]];
    }

    /**
     * Start adding a passkey to the current account. The current password is asked first:
     * a stolen auth cookie alone must not be enough to attach a sign-in method of one's own.
     *
     * @param array<string, mixed> $input Current `password`.
     * @return array<int|string, mixed> Options, or a notice fragment for a wrong password.
     */
    public function passkeyCreateOptions(array $input): array
    {
        $user = User::current();
        if (! password_verify(trim((string) ($input['password'] ?? '')), $user->password)) {
            return [['target' => 'body', 'notify' => t('The current password is incorrect.')]];
        }

        return ['options' => new Passkey()->creationOptions($user)];
    }

    /**
     * Finish adding a passkey: verify the attestation and store the public key.
     *
     * @param array<string, mixed> $input `credential` JSON and an optional `name`.
     * @return array<int, array<string, mixed>> Notice and card fragments.
     */
    public function passkeyCreate(array $input): array
    {
        try {
            $passkey = new Passkey()->create(User::current(), (string) ($input['credential'] ?? ''), (string) ($input['name'] ?? ''));
        } catch (Throwable) {
            return [['target' => 'body', 'notify' => t('Could not add the passkey. Please try again.')]];
        }

        return [
            ['target' => 'body', 'notify' => t('Passkey added to your account.')],
            ['target' => '#passkeys', 'prepend' => view('parts/passkey', ['passkey' => $passkey])->render()],
            ['target' => '#passkey-password', 'value' => ''],
        ];
    }

    /**
     * Remove a passkey of the current account.
     *
     * @param array<string, mixed> $input Passkey `id`.
     * @return array<int, array<string, mixed>> Notice and removal fragments.
     */
    public function passkeyDelete(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        if (! Passkey::delete(User::current(), $id)) {
            return [['target' => 'body', 'notify' => t('Passkey not found.')]];
        }

        return [
            ['target' => 'body', 'notify' => t('Passkey removed.')],
            ['target' => "#passkey-$id", 'remove' => true],
        ];
    }

    /**
     * Change the password of the current user after checking the current one;
     * other devices are signed out, this one stays signed in.
     *
     * @param array<string, mixed> $input `current` and new `password`.
     * @return array<int, array<string, mixed>> Notice and field reset fragments.
     */
    public function passwordUpdate(array $input): array
    {
        $user = User::current();
        if (! password_verify(trim((string) ($input['current'] ?? '')), $user->password)) {
            return [['target' => 'body', 'notify' => t('The current password is incorrect.')]];
        }

        $updated = $user->changePassword(trim((string) ($input['password'] ?? '')));
        if ($updated instanceof Error) {
            return [['target' => 'body', 'notify' => $updated->get('user-password')[0] ?? t('Could not update the password. Please try again.')]];
        }

        $this->notifyPasswordChanged($user);

        return [
            ['target' => 'body', 'notify' => t('Your password has been changed. Other devices have been signed out.')],
            ['target' => '[name="password-new"], [name="password-old"]', 'value' => ''],
        ];
    }

    /**
 * Same redirect-fragment key fix as signIn() — was `method`/`fragment`, now `redirect`.
*/
    public function signUp(array $input): array|User
    {
        $user = User::create($input);

        if ($user instanceof User) {
            return [
                'signed-up' => true,
                ['target' => 'body', 'redirect' => url('sign-in')],
            ];
        }

        return $user;
    }

    /**
     * Password recovery in two steps: with an `email` sends a one-hour reset link, with the link's
     * `token` sets the new `password`. The first step answers the same whether the account exists,
     * so it can't be used to find registered emails.
     *
     * @param array<string, mixed> $input `email`, or `token` and `password`.
     * @return array<int, array<string, mixed>> Notice and redirect fragments.
     */
    public function resetPassword(array $input): array
    {
        $token = trim((string) ($input['token'] ?? ''));

        return $token === ''
            ? $this->sendPasswordReset(Safe::email($input['email'] ?? ''))
            : $this->completePasswordReset($token, trim((string) ($input['password'] ?? '')));
    }

    /**
     * Store a hashed reset token and email the link with the raw one.
     *
     * @param string $email
     * @return array<int, array<string, mixed>>
     */
    private function sendPasswordReset(string $email): array
    {
        $notice = [['target' => 'body', 'notify' => t('If the account exists, password reset instructions have been sent.')]];

        $user = $email === '' ? null : User::find($email, 'email');
        if (! $user instanceof User) {
            return $notice;
        }

        $token   = bin2hex(random_bytes(32));
        $updated = $user->update([
            'password_reset_token'      => hash('sha256', $token),
            'password_reset_expires_at' => date('Y-m-d H:i:s', time() + self::PASSWORD_RESET_TTL),
        ]);

        if (! $updated instanceof User) {
            Log::error('Password reset: could not save the token.', ['user' => $user->id]);

            return $notice;
        }

        $body = View::create('mails/wrapper', [
            'body_template' => 'mails/reset-password',
            'name'          => $user->showname ?: $user->login,
            'resetUrl'      => url('reset-password?token=' . $token),
        ])->render();

        $message = Mail::to($email)->subject(t('Password reset instructions'))->message($body);
        if (! $message->send()) {
            Log::error('Password reset: the email was not sent.', ['user' => $user->id, 'error' => $message->error]);
        }

        return $notice;
    }

    /**
     * Set a new password by a reset token; the token works once and changing the password
     * signs the account out everywhere, since auth cookies are signed with the password hash.
     *
     * @param string $token    Raw token from the link.
     * @param string $password New password.
     * @return array<int, array<string, mixed>>
     */
    private function completePasswordReset(string $token, string $password): array
    {
        $invalid = [['target' => 'body', 'notify' => t('This password reset link is invalid or expired.')]];
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $invalid;
        }

        $id   = Db::get('users', 'id', ['password_reset_token' => hash('sha256', $token)]);
        $user = $id ? User::find((int) $id) : null;
        if (! $user instanceof User || ($user->passwordResetExpiresAt?->getTimestamp() ?? 0) < time()) {
            return $invalid;
        }

        $updated = $user->changePassword($password);
        if ($updated instanceof Error) {
            return [['target' => 'body', 'notify' => $updated->get('user-password')[0] ?? t('Could not update the password. Please try again.')]];
        }

        $this->notifyPasswordChanged($user);

        return [
            ['target' => 'body', 'notify' => t('Your password has been changed. Sign in with the new password.')],
            ['target' => 'body', 'redirect:1500' => url('sign-in')],
        ];
    }

    /**
     * Notify the account owner after a successful password change without affecting the completed change on mail failure.
     *
     * @param User $user Account owner.
     * @return void
     */
    private function notifyPasswordChanged(User $user): void
    {
        try {
            $body = View::create('mails/wrapper', [
                'body_template' => 'mails/password-changed',
                'name'          => $user->showname ?: $user->login,
                'siteUrl'       => url(),
            ])->render();

            $message = Mail::to($user->email)->subject(t('Your password was changed'))->message($body);
            if (! $message->send()) {
                Log::error('Password changed: the notification email was not sent.', ['user' => $user->id, 'error' => $message->error]);
            }
        } catch (Throwable $error) {
            Log::error('Password changed: the notification email failed.', ['user' => $user->id, 'error' => $error->getMessage()]);
        }
    }
}
