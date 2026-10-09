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
use Expansa\Http\Exceptions\ValidationFailed;
use Expansa\Http\Request;
use Expansa\Http\Response;
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
     * @param Request  $request  Profile fields and the custom fields `bio`, `toolbar`, `format`.
     * @param Response $response
     * @return Response Notice.
     * @throws ValidationFailed When the new email is taken.
     */
    public function update(Request $request, Response $response): Response
    {
        $user  = User::current();
        $data  = array_intersect_key($request->post, array_flip(self::PROFILE_FIELDS));
        $email = Safe::email($data['email'] ?? $user->email);
        unset($data['email']);

        $isNewEmail = $email !== '' && mb_strtolower($email) !== mb_strtolower($user->email);
        if ($isNewEmail && ! Confirmation::check($user)) {
            return $response->notify(t('To change the email, confirm it is you in the Security tab first.'));
        }

        if ($isNewEmail && User::find($email, 'email') instanceof User) {
            throw ValidationFailed::field('email', t('Sorry, that email address is already in use.'));
        }

        $fields = Safe::data($request->post, [
            'bio'     => 'trim',
            'toolbar' => 'bool',
            'format'  => 'text',
        ])->apply();

        $updated = $user->update($data);
        if (! $updated instanceof User) {
            return $response->notify(t('Could not update the profile. Please try again.'));
        }

        foreach ($fields as $key => $value) {
            $updated->field->update($key, $value);
        }

        if ($isNewEmail) {
            Verification::changeEmail($updated, $email);

            return $response->notify(t('User updated. Open the link we have sent to :email to change the email.', $email));
        }

        return $response->notify(t('User updated.'));
    }

    /**
     * Sign in by a login (or email) and password, then go to the page the user came for.
     *
     * @param Request  $request  `login`, `password`, `remember`, `redirect_to`.
     * @param Response $response
     * @return Response Redirect or notice.
     */
    public function signIn(Request $request, Response $response): Response
    {
        $user = SignIn::password($request->post);
        if ($user instanceof Error) {
            return $response->notify($user->messages[0]);
        }

        $url = SignIn::finish($user, Safe::bool($request->post['remember'] ?? false), 'password', (string) ($request->post['redirect_to'] ?? ''));

        return $response->redirect($url);
    }

    /**
     * Send a sign-in link to an email, when the Security settings allow it. The answer is the same
     * whether the account exists, so it can't be used to find registered emails.
     *
     * @param Request  $request  `email`, `redirect_to`.
     * @param Response $response
     * @return Response Notice.
     * @throws TooManyAttempts If the IP asks too often.
     */
    public function emailLink(Request $request, Response $response): Response
    {
        if (! EmailLink::isEnabled()) {
            return $response->notify(t('Signing in by an email link is turned off.'));
        }

        Auth::limit('email-link:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600);
        EmailLink::send(Safe::email($request->post['email'] ?? ''), (string) ($request->post['redirect_to'] ?? ''));

        return $response->notify(t('If the account exists, a sign-in link has been sent to the email.'));
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
     * @param Request  $request  `credential` JSON, `remember`, `redirect_to`.
     * @param Response $response
     * @return Response Redirect or error notice.
     * @throws TooManyAttempts If the IP tries too often.
     */
    public function passkeySignIn(Request $request, Response $response): Response
    {
        Auth::limit('passkey:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 30, 300);

        try {
            $user = new Passkey()->verify((string) ($request->post['credential'] ?? ''));
        } catch (Throwable) {
            return $response->notify(t('The passkey could not be verified. Please try again.'));
        }

        $refusal = SignIn::refusal($user);
        if ($refusal instanceof Error) {
            return $response->notify($refusal->messages[0]);
        }

        $url = SignIn::finish($user, Safe::bool($request->post['remember'] ?? false), 'passkey', (string) ($request->post['redirect_to'] ?? ''));

        return $response->redirect($url);
    }

    /**
     * Confirm it is the owner by the current password, for the security changes of the next minutes.
     *
     * @param Request  $request  `password`.
     * @param Response $response
     * @return Response Notice.
     * @throws TooManyAttempts After too many wrong passwords.
     */
    public function confirm(Request $request, Response $response): Response
    {
        if (! Confirmation::confirm(User::current(), trim((string) ($request->post['password'] ?? '')))) {
            return $response->notify(t('The current password is incorrect.'));
        }

        return $this->confirmed($response);
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
     * @param Request  $request  `credential` JSON.
     * @param Response $response
     * @return Response Notice.
     */
    public function confirmPasskey(Request $request, Response $response): Response
    {
        if (! Confirmation::confirmWithPasskey(User::current(), (string) ($request->post['credential'] ?? ''))) {
            return $response->notify(t('The passkey could not be verified. Please try again.'));
        }

        return $this->confirmed($response);
    }

    /**
     * Second step of a sign-in: a code of the authenticator app or a recovery code.
     *
     * @param Request  $request  `code`.
     * @param Response $response
     * @return Response Redirect or notice.
     */
    public function twoFactor(Request $request, Response $response): Response
    {
        $url = TwoFactor::complete(trim((string) ($request->post['code'] ?? '')));
        if ($url instanceof Error) {
            return $response->notify($url->messages[0]);
        }

        return $response->redirect($url);
    }

    /**
     * Start setting up two-factor authentication, after a confirmation: the QR code and the secret.
     *
     * @param Request  $request  May carry the current `password`.
     * @param Response $response
     * @return Response The form of the setup, or a notice.
     */
    public function twoFactorSetup(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        return $response->update('#two-factor', view('parts/two-factor', ['user' => $user, 'setup' => TwoFactor::setup($user)])->render());
    }

    /**
     * Turn two-factor authentication on by a code of the app; the recovery codes are shown once.
     *
     * @param Request  $request  `code`.
     * @param Response $response
     * @return Response The form with the recovery codes, or a notice.
     */
    public function twoFactorEnable(Request $request, Response $response): Response
    {
        $user  = User::current();
        $codes = TwoFactor::enable($user, trim((string) ($request->post['code'] ?? '')));
        if ($codes === null) {
            return $response->notify(t('The code is wrong. Check the time on your phone and try again.'));
        }

        return $response
            ->notify(t('Two-factor authentication is on.'))
            ->update('#two-factor', view('parts/two-factor', ['user' => $user, 'codes' => $codes])->render());
    }

    /**
     * Turn two-factor authentication off, after a confirmation, unless the role requires it.
     *
     * @param Request  $request  May carry the current `password`.
     * @param Response $response
     * @return Response
     */
    public function twoFactorDisable(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        if (TwoFactor::isRequired($user)) {
            return $response->notify(t('Your role requires two-factor authentication.'));
        }

        TwoFactor::disable($user);

        return $response
            ->notify(t('Two-factor authentication is off.'))
            ->update('#two-factor', view('parts/two-factor', ['user' => $user])->render());
    }

    /**
     * Replace the recovery codes, after a confirmation.
     *
     * @param Request  $request  May carry the current `password`.
     * @param Response $response
     * @return Response The form with the new codes, or a notice.
     */
    public function twoFactorCodes(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $codes = TwoFactor::regenerateCodes($user);

        return $response->update('#two-factor', view('parts/two-factor', ['user' => $user, 'codes' => $codes])->render());
    }

    /**
     * Start adding a passkey to the current account, after a confirmation.
     *
     * @param Request  $request  May carry the current `password`.
     * @param Response $response
     * @return array<string, mixed>|Response Options, or a notice.
     */
    public function passkeyCreateOptions(Request $request, Response $response): array|Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        return ['options' => new Passkey()->creationOptions($user)];
    }

    /**
     * Finish adding a passkey: verify the attestation and store the public key.
     * The challenge comes only from passkeyCreateOptions(), so the confirmation is already checked.
     *
     * @param Request  $request  `credential` JSON and an optional `name`.
     * @param Response $response
     * @return Response Notice and card.
     */
    public function passkeyCreate(Request $request, Response $response): Response
    {
        $user = User::current();

        try {
            $passkey = new Passkey()->create($user, (string) ($request->post['credential'] ?? ''), (string) ($request->post['name'] ?? ''));
        } catch (Throwable) {
            return $response->notify(t('Could not add the passkey. Please try again.'));
        }

        Events::record($user, 'passkey_added', ['name' => $passkey['name']]);

        return $response
            ->notify(t('Passkey added to your account.'))
            ->prepend('#passkeys', view('parts/passkey', ['passkey' => $passkey])->render());
    }

    /**
     * Remove a passkey of the current account, after a confirmation.
     *
     * @param Request  $request  Passkey `id`, may carry the current `password`.
     * @param Response $response
     * @return Response Notice and removal.
     */
    public function passkeyDelete(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $id = (int) ($request->post['id'] ?? 0);
        if (! Passkey::delete($user, $id)) {
            return $response->notify(t('Passkey not found.'));
        }

        Events::record($user, 'passkey_removed');

        return $response
            ->notify(t('Passkey removed.'))
            ->remove("#passkey-$id");
    }

    /**
     * Start connecting a sign-in provider to the current account, after a confirmation.
     *
     * @param Request  $request  `provider`, may carry the current `password`.
     * @param Response $response
     * @return Response Redirect to the provider, or a notice.
     */
    public function identityConnect(Request $request, Response $response): Response
    {
        if (! Confirmation::check(User::current(), $request->post)) {
            return $this->unconfirmed($response);
        }

        try {
            $url = Identities::start((string) ($request->post['provider'] ?? ''), link: true);
        } catch (Throwable) {
            return $response->notify(t('Could not connect the account. Please try again.'));
        }

        return $response->redirect($url);
    }

    /**
     * Disconnect a sign-in provider from the current account, after a confirmation.
     *
     * @param Request  $request  Connected account `id`, may carry the current `password`.
     * @param Response $response
     * @return Response Notice and removal.
     */
    public function identityDelete(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $id = (int) ($request->post['id'] ?? 0);
        if (! Identities::delete($user, $id)) {
            return $response->notify(t('Account not found.'));
        }

        Events::record($user, 'provider_disconnected');

        return $response
            ->notify(t('Account disconnected.'))
            ->remove("#identity-$id");
    }

    /**
     * Make another account signed in on this browser current.
     *
     * @param Request  $request  `login` of the account.
     * @param Response $response
     * @return Response Redirect to the dashboard, or a notice.
     */
    public function switchAccount(Request $request, Response $response): Response
    {
        if (! Auth::switchAccount((string) ($request->post['login'] ?? ''))) {
            return $response->notify(t('This account is signed out. Sign in to it again.'));
        }

        return $response->redirect(url('dashboard'));
    }

    /**
     * Sign out another device of the current user, after a confirmation.
     *
     * @param Request  $request  Session `id` from the profile, may carry the current `password`.
     * @param Response $response
     * @return Response Notice and removal.
     */
    public function sessionDelete(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $id = (int) ($request->post['id'] ?? 0);
        if (! Sessions::deleteById($user, $id)) {
            return $response->notify(t('Device not found.'));
        }

        Events::record($user, 'session_revoked');

        return $response
            ->notify(t('The device has been signed out.'))
            ->remove("#session-$id");
    }

    /**
     * Sign out every device of the current user except this one, after a confirmation.
     *
     * @param Request  $request  May carry the current `password`.
     * @param Response $response
     * @return Response Notice and removal.
     */
    public function sessionsDeleteOthers(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $count = Sessions::deleteOthers($user);
        Events::record($user, 'sessions_revoked', ['count' => $count]);

        return $response
            ->notify(t('Signed out of other devices: :count.', $count))
            ->remove('[data-session-other]');
    }

    /**
     * Change the password of the current user after checking the current one;
     * other devices are signed out, this one stays signed in.
     *
     * @param Request  $request  `current` and new `password`.
     * @param Response $response
     * @return Response Notice and field reset.
     * @throws TooManyAttempts After too many wrong current passwords.
     * @throws ValidationFailed When a password is wrong or refused.
     */
    public function passwordUpdate(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::confirm($user, trim((string) ($request->post['current'] ?? '')))) {
            throw ValidationFailed::field('current', t('The current password is incorrect.'));
        }

        $updated = $user->changePassword(trim((string) ($request->post['password'] ?? '')));
        if ($updated instanceof Error) {
            return $this->passwordRefused($response, $updated);
        }

        $this->notifyPasswordChanged($user);

        return $response
            ->notify(t('Your password has been changed. Other devices have been signed out.'))
            ->value('[name="password-new"], [name="password-old"]', '');
    }

    /**
     * Create a personal API token, after a confirmation; it is shown once.
     *
     * @param Request  $request  `name`, `days`, comma-separated `scopes`, may carry the current `password`.
     * @param Response $response
     * @return Response With the token, or a notice.
     */
    public function tokenCreate(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $scopes = array_filter(array_map(trim(...), explode(',', (string) ($request->post['scopes'] ?? ''))));
        if ($scopes === []) {
            return $response->notify(t('Choose at least one permission for the token.'));
        }

        $token = Tokens::create($user, (string) ($request->post['name'] ?? ''), $scopes, (int) ($request->post['days'] ?? 0));

        return $response
            ->notify(t('Token created. Copy it now: it is not shown again.'))
            ->update('#token-created', '<code class="p-3 card card-border fs-13">' . htmlspecialchars($token) . '</code>')
            ->update('#tokens', view('parts/tokens', ['tokens' => Tokens::all($user)])->render());
    }

    /**
     * Revoke a personal API token, after a confirmation.
     *
     * @param Request  $request  Token `id`, may carry the current `password`.
     * @param Response $response
     * @return Response
     */
    public function tokenDelete(Request $request, Response $response): Response
    {
        $user = User::current();
        if (! Confirmation::check($user, $request->post)) {
            return $this->unconfirmed($response);
        }

        $id = (int) ($request->post['id'] ?? 0);
        if (! Tokens::delete($user, $id)) {
            return $response->notify(t('Token not found.'));
        }

        return $response
            ->notify(t('Token revoked.'))
            ->remove("#token-$id");
    }

    /**
     * Administrator: turn an account on or off, after a confirmation.
     *
     * @param Request  $request  `id`, `active`, may carry the current `password`.
     * @param Response $response
     * @return Response
     */
    public function adminStatus(Request $request, Response $response): Response
    {
        return $this->administer($request, $response, true, function (User $admin, User $user) use ($request): string {
            $isActive = Safe::bool($request->post['active'] ?? false);

            return Admin::setActive($admin, $user, $isActive) ?? ($isActive ? t('The account is on.') : t('The account is disabled and signed out.'));
        });
    }

    /**
     * Administrator: sign an account out of every device, after a confirmation.
     *
     * @param Request  $request  `id`, may carry the current `password`.
     * @param Response $response
     * @return Response
     */
    public function adminSignOut(Request $request, Response $response): Response
    {
        return $this->administer($request, $response, true, fn (User $admin, User $user) => t('Signed out of devices: :count.', Admin::signOut($admin, $user)));
    }

    /**
     * Administrator: send an account a password reset link.
     *
     * @param Request  $request  `id`.
     * @param Response $response
     * @return Response
     */
    public function adminPasswordReset(Request $request, Response $response): Response
    {
        return $this->administer($request, $response, false, function (User $admin, User $user): string {
            Admin::sendPasswordReset($admin, $user);

            return t('A password reset link has been sent to :email.', $user->email);
        });
    }

    /**
     * Administrator: turn the two-factor authentication of an account off, after a confirmation.
     *
     * @param Request  $request  `id`, may carry the current `password`.
     * @param Response $response
     * @return Response
     */
    public function adminTwoFactorDisable(Request $request, Response $response): Response
    {
        return $this->administer($request, $response, true, function (User $admin, User $user): string {
            Admin::disableTwoFactor($admin, $user);

            return t('Two-factor authentication of the account is off.');
        });
    }

    /**
     * Administrator: sign in as the user, after a confirmation.
     *
     * @param Request  $request  `id`, may carry the current `password`.
     * @param Response $response
     * @return Response
     */
    public function impersonate(Request $request, Response $response): Response
    {
        $admin = User::current();
        $user  = Admin::find((int) ($request->post['id'] ?? 0));
        if ($user === null) {
            return $response->notify(t('User not found.'));
        }

        if (! Confirmation::check($admin, $request->post)) {
            return $response->notify(t('Confirm it is you: enter your current password.'));
        }

        $refusal = Admin::impersonate($admin, $user);
        if ($refusal !== null) {
            return $response->notify($refusal);
        }

        return $response->redirect(url('dashboard'));
    }

    /**
     * Go back from an impersonated account to the administrator's own.
     *
     * @param Response $response
     * @return Response
     */
    public function stopImpersonating(Response $response): Response
    {
        if (! Admin::stopImpersonating()) {
            return $response->notify(t('You are not signed in as another user.'));
        }

        return $response->redirect(url('dashboard/users'));
    }

    /**
     * Sign up while registration is open; the account signs in after its email is confirmed.
     *
     * @param Request  $request  `login`, `email`, `password`.
     * @param Response $response
     * @return Response Notice and redirect.
     * @throws TooManyAttempts If the IP signs up too often.
     * @throws ValidationFailed With the fields that are refused.
     */
    public function signUp(Request $request, Response $response): Response
    {
        if (! Option::get('users.membership')) {
            return $response->notify(t('Registration is closed.'));
        }

        Auth::limit('sign-up:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600);

        $personal = [(string) ($request->input['login'] ?? ''), (string) ($request->input['email'] ?? '')];
        $refusal  = Passwords::check((string) ($request->input['password'] ?? ''), $personal);
        if ($refusal !== null) {
            throw ValidationFailed::field('password', $refusal);
        }

        $user = User::create(array_intersect_key($request->input, array_flip(['login', 'email', 'password'])) + ['is_verified' => false]);
        if ($user instanceof Error) {
            throw ValidationFailed::from($user, t('Could not create the account. Check the fields.'));
        }

        Verification::send($user);

        return $response
            ->notify(t('Almost done: open the link we have sent to your email to confirm it.'))
            ->redirect(url('sign-in'), 2500);
    }

    /**
     * Password recovery in two steps: with an `email` sends a one-hour reset link, with the link's
     * `token` sets the new `password`. The first step answers the same whether the account exists,
     * so it can't be used to find registered emails.
     *
     * @param Request  $request  `email`, or `token` and `password`.
     * @param Response $response
     * @return Response Notice and redirect.
     * @throws TooManyAttempts If the IP asks too often.
     * @throws ValidationFailed When the new password is refused.
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        $token = trim((string) ($request->input['token'] ?? ''));
        if ($token !== '') {
            return $this->completePasswordReset($response, $token, trim((string) ($request->input['password'] ?? '')));
        }

        Auth::limit('reset:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 3600);

        $email = Safe::email($request->input['email'] ?? '');
        $user  = $email === '' ? null : User::find($email, 'email');
        if ($user instanceof User) {
            $this->requestPasswordReset($user);
        }

        return $response->notify(t('If the account exists, password reset instructions have been sent.'));
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
     * @param Response $response
     * @param string   $token    Raw token from the link.
     * @param string   $password New password.
     * @return Response
     * @throws ValidationFailed When the password rules refuse it.
     */
    private function completePasswordReset(Response $response, string $token, string $password): Response
    {
        $invalid = $response->notify(t('This password reset link is invalid or expired.'));
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
            return $this->passwordRefused($response, $updated);
        }

        $this->notifyPasswordChanged($user);

        return $response
            ->notify(t('Your password has been changed. Sign in with the new password.'))
            ->redirect(url('sign-in'), 1500);
    }

    /**
     * Run an administrator action on the account of `id`.
     *
     * @param Request                      $request           `id` of the account, may carry the current `password`.
     * @param Response                     $response
     * @param bool                         $needsConfirmation Ask for the administrator's password first.
     * @param callable(User, User): string $action            Returns the notice.
     * @return Response
     */
    private function administer(Request $request, Response $response, bool $needsConfirmation, callable $action): Response
    {
        $admin = User::current();
        $user  = Admin::find((int) ($request->post['id'] ?? 0));
        if ($user === null) {
            return $response->notify(t('User not found.'));
        }

        if ($needsConfirmation && ! Confirmation::check($admin, $request->post)) {
            return $response->notify(t('Confirm it is you: enter your current password.'));
        }

        return $response
            ->notify($action($admin, $user))
            ->reload(1200);
    }

    /**
     * Fragments of a successful confirmation.
     *
     * @param Response $response
     * @return Response
     */
    private function confirmed(Response $response): Response
    {
        return $response
            ->notify(t('Confirmed. Security changes will not ask again for :minutes min.', Confirmation::TTL / 60))
            ->value('#confirm-password', '');
    }

    /**
     * Fragments asking for a confirmation.
     *
     * @param Response $response
     * @return Response
     */
    private function unconfirmed(Response $response): Response
    {
        return $response->notify(t('Confirm it is you: enter your current password at the top of the Security tab.'));
    }

    /**
     * A refused new password is shown at the `password` field; a failed save stays a notice.
     *
     * @param Response $response
     * @param Error    $error    Result of User::changePassword().
     * @return Response
     * @throws ValidationFailed When the password rules refuse it.
     */
    private function passwordRefused(Response $response, Error $error): Response
    {
        $message = $error->messages[0] ?? t('Could not update the password. Please try again.');
        if ($error->code === 'user-password') {
            throw ValidationFailed::field('password', $message);
        }

        return $response->notify($message);
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
