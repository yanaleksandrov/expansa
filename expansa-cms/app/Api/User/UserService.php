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
     * Update the profile of the current user. A new email needs a recent confirmation (see Confirmation:
     * whoever holds the cookie must not take the account over by its email) and replaces the old one only
     * after its link is opened, see Verification::changeEmail().
     *
     * @param array<string, mixed> $input Profile fields and the custom fields `bio`, `toolbar`, `format`.
     * @return array<int, array<string, mixed>> Notice fragment.
     */
    public function update(array $input): array
    {
        $user  = User::current();
        $data  = array_intersect_key($input, array_flip(self::PROFILE_FIELDS));
        $email = Safe::email($data['email'] ?? $user->email);
        unset($data['email']);

        $isNewEmail = $email !== '' && mb_strtolower($email) !== mb_strtolower($user->email);
        if ($isNewEmail && ! Confirmation::check($user)) {
            return [['target' => 'body', 'notify' => t('To change the email, confirm it is you in the Security tab first.')]];
        }

        if ($isNewEmail && User::find($email, 'email') instanceof User) {
            return [['target' => 'body', 'notify' => t('Sorry, that email address or login is already in use.')]];
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
            Verification::changeEmail($updated, $email);

            return [['target' => 'body', 'notify' => t('User updated. Open the link we have sent to :email to change the email.', $email)]];
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
     * Send a sign-in link to an email, when the Security settings allow it. The answer is the same
     * whether the account exists, so it can't be used to find registered emails.
     *
     * @param array<string, mixed> $input `email`, `redirect_to`.
     * @return array<int, array<string, mixed>> Notice fragment.
     * @throws TooManyAttempts If the IP asks too often.
     */
    public function emailLink(array $input): array
    {
        if (! EmailLink::isEnabled()) {
            return [['target' => 'body', 'notify' => t('Signing in by an email link is turned off.')]];
        }

        Auth::limit('email-link:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600);
        EmailLink::send(Safe::email($input['email'] ?? ''), (string) ($input['redirect_to'] ?? ''));

        return [['target' => 'body', 'notify' => t('If the account exists, a sign-in link has been sent to the email.')]];
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
     * Second step of a sign-in: a code of the authenticator app or a recovery code.
     *
     * @param array<string, mixed> $input `code`.
     * @return array<int, array<string, mixed>> Redirect or notice fragment.
     */
    public function twoFactor(array $input): array
    {
        $url = TwoFactor::complete(trim((string) ($input['code'] ?? '')));
        if ($url instanceof Error) {
            return [['target' => 'body', 'notify' => $url->messages[0]]];
        }

        return [['target' => 'body', 'redirect' => $url]];
    }

    /**
     * Start setting up two-factor authentication, after a confirmation: the QR code and the secret.
     *
     * @param array<string, mixed> $input May carry the current `password`.
     * @return array<int, array<string, mixed>> Fragment of the setup, or a notice.
     */
    public function twoFactorSetup(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        return [['target' => '#two-factor', 'update' => view('parts/two-factor', ['user' => $user, 'setup' => TwoFactor::setup($user)])->render()]];
    }

    /**
     * Turn two-factor authentication on by a code of the app; the recovery codes are shown once.
     *
     * @param array<string, mixed> $input `code`.
     * @return array<int, array<string, mixed>> Fragment with the recovery codes, or a notice.
     */
    public function twoFactorEnable(array $input): array
    {
        $user  = User::current();
        $codes = TwoFactor::enable($user, trim((string) ($input['code'] ?? '')));
        if ($codes === null) {
            return [['target' => 'body', 'notify' => t('The code is wrong. Check the time on your phone and try again.')]];
        }

        return [
            ['target' => 'body', 'notify' => t('Two-factor authentication is on.')],
            ['target' => '#two-factor', 'update' => view('parts/two-factor', ['user' => $user, 'codes' => $codes])->render()],
        ];
    }

    /**
     * Turn two-factor authentication off, after a confirmation, unless the role requires it.
     *
     * @param array<string, mixed> $input May carry the current `password`.
     * @return array<int, array<string, mixed>> Fragments.
     */
    public function twoFactorDisable(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        if (TwoFactor::isRequired($user)) {
            return [['target' => 'body', 'notify' => t('Your role requires two-factor authentication.')]];
        }

        TwoFactor::disable($user);

        return [
            ['target' => 'body', 'notify' => t('Two-factor authentication is off.')],
            ['target' => '#two-factor', 'update' => view('parts/two-factor', ['user' => $user])->render()],
        ];
    }

    /**
     * Replace the recovery codes, after a confirmation.
     *
     * @param array<string, mixed> $input May carry the current `password`.
     * @return array<int, array<string, mixed>> Fragment with the new codes, or a notice.
     */
    public function twoFactorCodes(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $codes = TwoFactor::regenerateCodes($user);

        return [['target' => '#two-factor', 'update' => view('parts/two-factor', ['user' => $user, 'codes' => $codes])->render()]];
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
     * Create a personal API token, after a confirmation; it is shown once.
     *
     * @param array<string, mixed> $input `name`, `days`, comma-separated `scopes`, may carry the current `password`.
     * @return array<int, array<string, mixed>> Fragments with the token, or a notice.
     */
    public function tokenCreate(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $scopes = array_filter(array_map(trim(...), explode(',', (string) ($input['scopes'] ?? ''))));
        if ($scopes === []) {
            return [['target' => 'body', 'notify' => t('Choose at least one permission for the token.')]];
        }

        $token = Tokens::create($user, (string) ($input['name'] ?? ''), $scopes, (int) ($input['days'] ?? 0));

        return [
            ['target' => 'body', 'notify' => t('Token created. Copy it now: it is not shown again.')],
            ['target' => '#token-created', 'update' => '<code class="p-3 card card-border fs-13">' . htmlspecialchars($token) . '</code>'],
            ['target' => '#tokens', 'update' => view('parts/tokens', ['tokens' => Tokens::all($user)])->render()],
        ];
    }

    /**
     * Revoke a personal API token, after a confirmation.
     *
     * @param array<string, mixed> $input Token `id`, may carry the current `password`.
     * @return array<int, array<string, mixed>>
     */
    public function tokenDelete(array $input): array
    {
        $user = User::current();
        if (! Confirmation::check($user, $input)) {
            return $this->unconfirmed();
        }

        $id = (int) ($input['id'] ?? 0);
        if (! Tokens::delete($user, $id)) {
            return [['target' => 'body', 'notify' => t('Token not found.')]];
        }

        return [
            ['target' => 'body', 'notify' => t('Token revoked.')],
            ['target' => "#token-$id", 'remove' => true],
        ];
    }

    /**
     * Administrator: turn an account on or off, after a confirmation.
     *
     * @param array<string, mixed> $input `id`, `active`, may carry the current `password`.
     * @return array<int, array<string, mixed>>
     */
    public function adminStatus(array $input): array
    {
        return $this->administer($input, true, function (User $admin, User $user) use ($input): string {
            $isActive = Safe::bool($input['active'] ?? false);

            return Admin::setActive($admin, $user, $isActive) ?? ($isActive ? t('The account is on.') : t('The account is disabled and signed out.'));
        });
    }

    /**
     * Administrator: sign an account out of every device, after a confirmation.
     *
     * @param array<string, mixed> $input `id`, may carry the current `password`.
     * @return array<int, array<string, mixed>>
     */
    public function adminSignOut(array $input): array
    {
        return $this->administer($input, true, fn (User $admin, User $user) => t('Signed out of devices: :count.', Admin::signOut($admin, $user)));
    }

    /**
     * Administrator: send an account a password reset link.
     *
     * @param array<string, mixed> $input `id`.
     * @return array<int, array<string, mixed>>
     */
    public function adminPasswordReset(array $input): array
    {
        return $this->administer($input, false, function (User $admin, User $user): string {
            Admin::sendPasswordReset($admin, $user);

            return t('A password reset link has been sent to :email.', $user->email);
        });
    }

    /**
     * Administrator: turn the two-factor authentication of an account off, after a confirmation.
     *
     * @param array<string, mixed> $input `id`, may carry the current `password`.
     * @return array<int, array<string, mixed>>
     */
    public function adminTwoFactorDisable(array $input): array
    {
        return $this->administer($input, true, function (User $admin, User $user): string {
            Admin::disableTwoFactor($admin, $user);

            return t('Two-factor authentication of the account is off.');
        });
    }

    /**
     * Administrator: sign in as the user, after a confirmation.
     *
     * @param array<string, mixed> $input `id`, may carry the current `password`.
     * @return array<int, array<string, mixed>>
     */
    public function impersonate(array $input): array
    {
        $admin = User::current();
        $user  = Admin::find((int) ($input['id'] ?? 0));
        if ($user === null) {
            return [['target' => 'body', 'notify' => t('User not found.')]];
        }

        if (! Confirmation::check($admin, $input)) {
            return [['target' => 'body', 'notify' => t('Confirm it is you: enter your current password.')]];
        }

        $refusal = Admin::impersonate($admin, $user);
        if ($refusal !== null) {
            return [['target' => 'body', 'notify' => $refusal]];
        }

        return [['target' => 'body', 'redirect' => url('dashboard')]];
    }

    /**
     * Go back from an impersonated account to the administrator's own.
     *
     * @return array<int, array<string, mixed>>
     */
    public function stopImpersonating(): array
    {
        if (! Admin::stopImpersonating()) {
            return [['target' => 'body', 'notify' => t('You are not signed in as another user.')]];
        }

        return [['target' => 'body', 'redirect' => url('dashboard/users')]];
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
     * Run an administrator action on the account of `id`.
     *
     * @param array<string, mixed>        $input
     * @param bool                        $needsConfirmation Ask for the administrator's password first.
     * @param callable(User, User): string $action           Returns the notice.
     * @return array<int, array<string, mixed>>
     */
    private function administer(array $input, bool $needsConfirmation, callable $action): array
    {
        $admin = User::current();
        $user  = Admin::find((int) ($input['id'] ?? 0));
        if ($user === null) {
            return [['target' => 'body', 'notify' => t('User not found.')]];
        }

        if ($needsConfirmation && ! Confirmation::check($admin, $input)) {
            return [['target' => 'body', 'notify' => t('Confirm it is you: enter your current password.')]];
        }

        return [
            ['target' => 'body', 'notify' => $action($admin, $user)],
            ['target' => 'body', 'reload:1200' => true],
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
