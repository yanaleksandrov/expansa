<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\Option;
use App\Models\User;
use App\Support\Passwords;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Log;
use Expansa\Facades\Mail;
use Expansa\Facades\Safe;
use Expansa\Facades\View;
use Expansa\Support\Error;
use Throwable;

final class UserService
{
    /**
     * Lifetime of a password reset link in seconds.
     */
    private const int PASSWORD_RESET_TTL = 3600;

    /**
     * Profile fields a user may change; status, roles and the password have their own ways.
     */
    private const array PROFILE_FIELDS = ['nicename', 'firstname', 'lastname', 'showname', 'locale', 'email'];

    /**
     * Update the profile of the current user. A new email needs a recent confirmation,
     * see Confirmation: whoever holds the cookie must not take the account over by its email.
     *
     * @param array<string, mixed> $input Profile fields and the custom fields `bio`, `toolbar`, `format`.
     * @return array<int, array<string, mixed>> Notice fragment.
     */
    public function update(array $input): array
    {
        $user = User::current();
        $data = array_intersect_key($input, array_flip(self::PROFILE_FIELDS));

        $isNewEmail = isset($data['email']) && mb_strtolower(trim((string) $data['email'])) !== mb_strtolower($user->email);
        if ($isNewEmail && ! Confirmation::check($user)) {
            return [['target' => 'body', 'notify' => t('To change the email, confirm it is you in the Security tab first.')]];
        }

        $fields = Safe::data($input, [
            'bio'     => 'trim',
            'toolbar' => 'bool',
            'format'  => 'text',
        ])->apply();

        $updated = $user->update($data);
        if (! $updated instanceof User) {
            return [['target' => 'body', 'notify' => t('Could not update the profile. Please try again.')]];
        }

        foreach ($fields as $key => $value) {
            $updated->field->update($key, $value);
        }

        if ($isNewEmail) {
            Events::record($updated, 'email_changed');
        }

        return [['target' => 'body', 'notify' => t('User updated.')]];
    }

    /**
     * Sign in by a login (or email) and password, then go to the page the user came for.
     *
     * @param array<string, mixed> $input `login`, `password`, `remember`, `redirect_to`.
     * @return array<int, array<string, mixed>> Redirect or notice fragment.
     */
    public function signIn(array $input): array
    {
        $user = SignIn::password($input);
        if ($user instanceof Error) {
            return [['target' => 'body', 'notify' => $user->messages[0]]];
        }

        $url = SignIn::finish($user, Safe::bool($input['remember'] ?? false), 'password', (string) ($input['redirect_to'] ?? ''));

        return [['target' => 'body', 'redirect' => $url]];
    }

    /**
     * Start a passkey sign-in: request options without a login, so the response
     * says nothing about which accounts exist or have passkeys.
     *
     * @return array<string, mixed>
     * @throws TooManyAttempts If the IP asks too often.
     */
    public function passkeyOptions(): array
    {
        Auth::limit('passkey:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 30, 300);

        return ['options' => new Passkey()->requestOptions()];
    }

    /**
     * Finish a passkey sign-in: verify the assertion, then go to the page the user came for.
     *
     * @param array<string, mixed> $input `credential` JSON, `remember`, `redirect_to`.
     * @return array<int, array<string, mixed>> Redirect or error notice fragments.
     * @throws TooManyAttempts If the IP tries too often.
     */
    public function passkeySignIn(array $input): array
    {
        Auth::limit('passkey:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 30, 300);

        try {
            $user = new Passkey()->verify((string) ($input['credential'] ?? ''));
        } catch (Throwable) {
            return [['target' => 'body', 'notify' => t('The passkey could not be verified. Please try again.')]];
        }

        $refusal = SignIn::refusal($user);
        if ($refusal instanceof Error) {
            return [['target' => 'body', 'notify' => $refusal->messages[0]]];
        }

        $url = SignIn::finish($user, Safe::bool($input['remember'] ?? false), 'passkey', (string) ($input['redirect_to'] ?? ''));

        return [['target' => 'body', 'redirect' => $url]];
    }

    /**
     * Confirm it is the owner by the current password, for the security changes of the next minutes.
     *
     * @param array<string, mixed> $input `password`.
     * @return array<int, array<string, mixed>> Notice fragments.
     * @throws TooManyAttempts After too many wrong passwords.
     */
    public function confirm(array $input): array
    {
        if (! Confirmation::confirm(User::current(), trim((string) ($input['password'] ?? '')))) {
            return [['target' => 'body', 'notify' => t('The current password is incorrect.')]];
        }

        return $this->confirmed();
    }

    /**
     * Start confirming by a passkey.
     *
     * @return array<string, mixed> Request options for the browser.
     */
    public function confirmPasskeyOptions(): array
    {
        return ['options' => new Passkey()->requestOptions()];
    }

    /**
     * Confirm it is the owner by a passkey of the account.
     *
     * @param array<string, mixed> $input `credential` JSON.
     * @return array<int, array<string, mixed>> Notice fragments.
     */
    public function confirmPasskey(array $input): array
    {
        if (! Confirmation::confirmWithPasskey(User::current(), (string) ($input['credential'] ?? ''))) {
            return [['target' => 'body', 'notify' => t('The passkey could not be verified. Please try again.')]];
        }

        return $this->confirmed();
    }

    /**
     * Start adding a passkey to the current account, after a confirmation.
     *
     * @param array<string, mixed> $input May carry the current `password`.
     * @return array<int|string, mixed> Options, or a notice fragment.
     */
    public function passkeyCreateOptions(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        return ['options' => new Passkey()->creationOptions($user)];
    }

    /**
     * Finish adding a passkey: verify the attestation and store the public key.
     * The challenge comes only from passkeyCreateOptions(), so the confirmation is already checked.
     *
     * @param array<string, mixed> $input `credential` JSON and an optional `name`.
     * @return array<int, array<string, mixed>> Notice and card fragments.
     */
    public function passkeyCreate(array $input): array
    {
        $user = User::current();

        try {
            $passkey = new Passkey()->create($user, (string) ($input['credential'] ?? ''), (string) ($input['name'] ?? ''));
        } catch (Throwable) {
            return [['target' => 'body', 'notify' => t('Could not add the passkey. Please try again.')]];
        }

        Events::record($user, 'passkey_added', ['name' => $passkey['name']]);

        return [
            ['target' => 'body', 'notify' => t('Passkey added to your account.')],
            ['target' => '#passkeys', 'prepend' => view('parts/passkey', ['passkey' => $passkey])->render()],
        ];
    }

    /**
     * Remove a passkey of the current account, after a confirmation.
     *
     * @param array<string, mixed> $input Passkey `id`, may carry the current `password`.
     * @return array<int, array<string, mixed>> Notice and removal fragments.
     */
    public function passkeyDelete(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $id = (int) ($input['id'] ?? 0);
        if (! Passkey::delete($user, $id)) {
            return [['target' => 'body', 'notify' => t('Passkey not found.')]];
        }

        Events::record($user, 'passkey_removed');

        return [
            ['target' => 'body', 'notify' => t('Passkey removed.')],
            ['target' => "#passkey-$id", 'remove' => true],
        ];
    }

    /**
     * Start connecting a sign-in provider to the current account, after a confirmation.
     *
     * @param array<string, mixed> $input `provider`, may carry the current `password`.
     * @return array<int, array<string, mixed>> Redirect to the provider, or a notice fragment.
     */
    public function identityConnect(array $input): array
    {
        if (! Confirmation::check(User::current(), $input)) {
            return $this->unconfirmed();
        }

        try {
            $url = Identities::start((string) ($input['provider'] ?? ''), link: true);
        } catch (Throwable) {
            return [['target' => 'body', 'notify' => t('Could not connect the account. Please try again.')]];
        }

        return [['target' => 'body', 'redirect' => $url]];
    }

    /**
     * Disconnect a sign-in provider from the current account, after a confirmation.
     *
     * @param array<string, mixed> $input Connected account `id`, may carry the current `password`.
     * @return array<int, array<string, mixed>> Notice and removal fragments.
     */
    public function identityDelete(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $id = (int) ($input['id'] ?? 0);
        if (! Identities::delete($user, $id)) {
            return [['target' => 'body', 'notify' => t('Account not found.')]];
        }

        Events::record($user, 'provider_disconnected');

        return [
            ['target' => 'body', 'notify' => t('Account disconnected.')],
            ['target' => "#identity-$id", 'remove' => true],
        ];
    }

    /**
     * Make another account signed in on this browser current.
     *
     * @param array<string, mixed> $input `login` of the account.
     * @return array<int, array<string, mixed>> Redirect to the dashboard, or a notice fragment.
     */
    public function switchAccount(array $input): array
    {
        if (! Auth::switchAccount((string) ($input['login'] ?? ''))) {
            return [['target' => 'body', 'notify' => t('This account is signed out. Sign in to it again.')]];
        }

        return [['target' => 'body', 'redirect' => url('dashboard')]];
    }

    /**
     * Sign out another device of the current user, after a confirmation.
     *
     * @param array<string, mixed> $input Session `id` from the profile, may carry the current `password`.
     * @return array<int, array<string, mixed>> Notice and removal fragments.
     */
    public function sessionDelete(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $id = (int) ($input['id'] ?? 0);
        if (! Sessions::deleteById($user, $id)) {
            return [['target' => 'body', 'notify' => t('Device not found.')]];
        }

        Events::record($user, 'session_revoked');

        return [
            ['target' => 'body', 'notify' => t('The device has been signed out.')],
            ['target' => "#session-$id", 'remove' => true],
        ];
    }

    /**
     * Sign out every device of the current user except this one, after a confirmation.
     *
     * @param array<string, mixed> $input May carry the current `password`.
     * @return array<int, array<string, mixed>> Notice and removal fragments.
     */
    public function sessionsDeleteOthers(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $count = Sessions::deleteOthers($user);
        Events::record($user, 'sessions_revoked', ['count' => $count]);

        return [
            ['target' => 'body', 'notify' => t('Signed out of other devices: :count.', $count)],
            ['target' => '[data-session-other]', 'remove' => true],
        ];
    }

    /**
     * Change the password of the current user after checking the current one;
     * other devices are signed out, this one stays signed in.
     *
     * @param array<string, mixed> $input `current` and new `password`.
     * @return array<int, array<string, mixed>> Notice and field reset fragments.
     * @throws TooManyAttempts After too many wrong current passwords.
     */
    public function passwordUpdate(array $input): array
    {
        $user = User::current();
        if (! Confirmation::confirm($user, trim((string) ($input['current'] ?? '')))) {
            return [['target' => 'body', 'notify' => t('The current password is incorrect.')]];
        }

        $updated = $user->changePassword(trim((string) ($input['password'] ?? '')));
        if ($updated instanceof Error) {
            $message = $updated->messages[0] ?? t('Could not update the password. Please try again.');

            return [['target' => 'body', 'notify' => $message]];
        }

        $this->notifyPasswordChanged($user);

        return [
            ['target' => 'body', 'notify' => t('Your password has been changed. Other devices have been signed out.')],
            ['target' => '[name="password-new"], [name="password-old"]', 'value' => ''],
        ];
    }

    /**
     * Sign up while registration is open; the account signs in after its email is confirmed.
     *
     * @param array<string, mixed> $input `login`, `email`, `password`.
     * @return array<int|string, mixed>|Error Notice and redirect fragments, or the validation error.
     * @throws TooManyAttempts If the IP signs up too often.
     */
    public function signUp(array $input): array|Error
    {
        if (! Option::get('users.membership')) {
            return [['target' => 'body', 'notify' => t('Registration is closed.')]];
        }

        Auth::limit('sign-up:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600);

        $personal = [(string) ($input['login'] ?? ''), (string) ($input['email'] ?? '')];
        $refusal  = Passwords::check((string) ($input['password'] ?? ''), $personal);
        if ($refusal !== null) {
            return [['target' => 'body', 'notify' => $refusal]];
        }

        $user = User::create(array_intersect_key($input, array_flip(['login', 'email', 'password'])) + ['is_verified' => false]);
        if (! $user instanceof User) {
            return $user;
        }

        Verification::send($user);

        return [
            'signed-up' => true,
            ['target' => 'body', 'notify' => t('Almost done: open the link we have sent to your email to confirm it.')],
            ['target' => 'body', 'redirect:2500' => url('sign-in')],
        ];
    }

    /**
     * Password recovery in two steps: with an `email` sends a one-hour reset link, with the link's
     * `token` sets the new `password`. The first step answers the same whether the account exists,
     * so it can't be used to find registered emails.
     *
     * @param array<string, mixed> $input `email`, or `token` and `password`.
     * @return array<int, array<string, mixed>> Notice and redirect fragments.
     * @throws TooManyAttempts If the IP asks too often.
     */
    public function resetPassword(array $input): array
    {
        $token = trim((string) ($input['token'] ?? ''));
        if ($token !== '') {
            return $this->completePasswordReset($token, trim((string) ($input['password'] ?? '')));
        }

        Auth::limit('reset:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600);

        $email = Safe::email($input['email'] ?? '');
        $user  = $email === '' ? null : User::find($email, 'email');
        if ($user instanceof User) {
            $this->requestPasswordReset($user);
        }

        return [['target' => 'body', 'notify' => t('If the account exists, password reset instructions have been sent.')]];
    }

    /**
     * Store a hashed reset token and email the link with the raw one; at most three in an hour per account,
     * so nobody can flood an inbox with resets.
     *
     * @param User $user
     * @return void
     */
    public function requestPasswordReset(User $user): void
    {
        try {
            Auth::limit("reset-account:$user->id", 3, 3600);
        } catch (TooManyAttempts) {
            return;
        }

        $token   = bin2hex(random_bytes(32));
        $updated = $user->update([
            'password_reset_token'      => hash('sha256', $token),
            'password_reset_expires_at' => date('Y-m-d H:i:s', time() + self::PASSWORD_RESET_TTL),
        ]);

        if (! $updated instanceof User) {
            Log::error('Password reset: could not save the token.', ['user' => $user->id]);

            return;
        }

        Events::record($user, 'password_reset');

        $body = View::create('mails/wrapper', [
            'body_template' => 'mails/reset-password',
            'name'          => $user->showname ?: $user->login,
            'resetUrl'      => url('reset-password?token=' . $token),
        ])->render();

        $message = Mail::to($user->email)->subject(t('Password reset instructions'))->message($body);
        if (! $message->send()) {
            Log::error('Password reset: the email was not sent.', ['user' => $user->id, 'error' => $message->error]);
        }
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
            $message = $updated->messages[0] ?? t('Could not update the password. Please try again.');

            return [['target' => 'body', 'notify' => $message]];
        }

        $this->notifyPasswordChanged($user);

        return [
            ['target' => 'body', 'notify' => t('Your password has been changed. Sign in with the new password.')],
            ['target' => 'body', 'redirect:1500' => url('sign-in')],
        ];
    }

    /**
     * Fragments of a successful confirmation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function confirmed(): array
    {
        return [
            ['target' => 'body', 'notify' => t('Confirmed. Security changes will not ask again for :minutes min.', Confirmation::TTL / 60)],
            ['target' => '#confirm-password', 'value' => ''],
        ];
    }

    /**
     * Fragments asking for a confirmation.
     *
     * @return array<int, array<string, mixed>>
     */
    private function unconfirmed(): array
    {
        return [['target' => 'body', 'notify' => t('Confirm it is you: enter your current password at the top of the Security tab.')]];
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
