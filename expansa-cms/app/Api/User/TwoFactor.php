<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\Option;
use App\Models\User;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Auth\Totp;
use Expansa\Codecs\QrCode;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Session;
use Expansa\Support\Error;

/**
 * Two-factor authentication by an authenticator app (TOTP), the `user_two_factor` table: the secret
 * encrypted with the site key, recovery codes only as keyed hashes. After a password or a provider the
 * sign-in waits in the session for a code; a passkey is a second factor by itself and skips it.
 */
final class TwoFactor
{
    /**
     * Session key of the secret being set up.
     */
    private const string SETUP_KEY = 'two_factor.setup';

    /**
     * Session key of the sign-in waiting for a code.
     */
    private const string CHALLENGE_KEY = 'two_factor.challenge';

    /**
     * Seconds a sign-in waits for its code.
     */
    private const int CHALLENGE_TTL = 300;

    /**
     * Recovery codes created at once.
     */
    private const int RECOVERY_CODES = 10;

    /**
     * Whether the user has turned two-factor authentication on.
     *
     * @param User $user
     * @return bool
     */
    public static function isEnabled(User $user): bool
    {
        return (bool) Db::has('user_two_factor', ['user_id' => $user->id]);
    }

    /**
     * Whether a role of the user must use two-factor authentication, see the Security settings.
     *
     * @param User $user
     * @return bool
     */
    public static function isRequired(User $user): bool
    {
        $roles = array_keys(array_filter((array) Option::get('security.two_factor_roles', [])));

        return array_intersect($roles, $user->roles) !== [];
    }

    /**
     * Start setting up: a new secret waits in the session until a code from the app proves it was added.
     *
     * @param User $user
     * @return array{secret: string, qr: string} The secret for typing and its QR code as SVG.
     */
    public static function setup(User $user): array
    {
        self::startSession();
        $secret = Totp::createSecret();
        Session::set(self::SETUP_KEY, ['user' => $user->id, 'secret' => $secret]);

        $issuer = (string) Option::get('site.name') ?: 'Expansa';

        return [
            'secret' => trim(chunk_split($secret, 4, ' ')),
            'qr'     => new QrCode()->render(Totp::getUri($secret, $user->login, $issuer), 4),
        ];
    }

    /**
     * Finish setting up by a code of the app.
     *
     * @param User   $user
     * @param string $code
     * @return string[]|null Recovery codes to show once, null for a wrong code or no setup.
     */
    public static function enable(User $user, string $code): ?array
    {
        self::startSession();
        $setup = Session::get(self::SETUP_KEY);
        if (! is_array($setup) || ($setup['user'] ?? 0) !== $user->id) {
            return null;
        }

        $step = Totp::verify((string) $setup['secret'], $code);
        if ($step === null) {
            return null;
        }

        [$codes, $hashes] = self::createRecoveryCodes();

        Db::delete('user_two_factor', ['user_id' => $user->id]);
        Db::insert('user_two_factor', [
            'user_id'        => $user->id,
            'secret'         => self::encrypt((string) $setup['secret']),
            'recovery_codes' => json_encode($hashes),
            'last_step'      => $step,
        ]);
        Session::forget(self::SETUP_KEY);
        Events::record($user, 'two_factor_enabled');

        return $codes;
    }

    /**
     * Turn two-factor authentication off.
     *
     * @param User $user
     * @return void
     */
    public static function disable(User $user): void
    {
        Db::delete('user_two_factor', ['user_id' => $user->id]);
        Events::record($user, 'two_factor_disabled');
    }

    /**
     * Replace the recovery codes, the old ones stop working.
     *
     * @param User $user
     * @return string[] New codes to show once.
     */
    public static function regenerateCodes(User $user): array
    {
        [$codes, $hashes] = self::createRecoveryCodes();
        Db::update('user_two_factor', ['recovery_codes' => json_encode($hashes)], ['user_id' => $user->id]);
        Events::record($user, 'recovery_codes_new');

        return $codes;
    }

    /**
     * Put a proven sign-in on hold until its code.
     *
     * @param User   $user
     * @param bool   $remember
     * @param string $method
     * @param string $redirectTo
     * @return string URL of the code form.
     */
    public static function challenge(User $user, bool $remember, string $method, string $redirectTo): string
    {
        self::startSession();
        Session::set(self::CHALLENGE_KEY, [
            'user'        => $user->id,
            'remember'    => $remember,
            'method'      => $method,
            'redirect_to' => $redirectTo,
            'until'       => time() + self::CHALLENGE_TTL,
        ]);

        return url('sign-in?step=two-factor');
    }

    /**
     * The user whose sign-in waits for a code.
     *
     * @return User|null
     */
    public static function getChallengedUser(): ?User
    {
        self::startSession();
        $challenge = Session::get(self::CHALLENGE_KEY);
        if (! is_array($challenge) || ($challenge['until'] ?? 0) < time()) {
            return null;
        }

        $user = User::find((int) $challenge['user']);

        return $user instanceof User ? $user : null;
    }

    /**
     * Finish a waiting sign-in by a code of the app or a recovery code.
     *
     * @param string $code
     * @return string|Error URL to go to, or why the code is refused.
     */
    public static function complete(string $code): string|Error
    {
        self::startSession();
        $challenge = Session::get(self::CHALLENGE_KEY);
        $user      = self::getChallengedUser();
        if ($user === null) {
            return error('two-factor', t('The sign-in has expired. Sign in again.'));
        }

        try {
            Auth::limit("two-factor:$user->id", 5, self::CHALLENGE_TTL);
        } catch (TooManyAttempts) {
            Session::forget(self::CHALLENGE_KEY);

            return error('two-factor', t('Too many wrong codes. Sign in again in a few minutes.'));
        }

        if (! self::verify($user, $code)) {
            Events::record($user, 'two_factor_failed');

            return error('two-factor', t('The code is wrong or already used.'));
        }

        Session::forget(self::CHALLENGE_KEY);

        return SignIn::complete(
            $user,
            (bool) $challenge['remember'],
            (string) $challenge['method'],
            (string) $challenge['redirect_to'],
        );
    }

    /**
     * Check a code of the app, once per time step, or use up a recovery code.
     *
     * @param User   $user
     * @param string $code
     * @return bool
     */
    private static function verify(User $user, string $code): bool
    {
        $row = Db::get('user_two_factor', ['secret', 'recovery_codes', 'last_step'], ['user_id' => $user->id]);
        if (! is_array($row)) {
            return false;
        }

        // the step only grows, so of two requests with the same code only one wins
        $step = Totp::verify(self::decrypt($row['secret']), $code, (int) $row['last_step']);
        if ($step !== null) {
            $updated = Db::update('user_two_factor', ['last_step' => $step], ['user_id' => $user->id, 'last_step[<]' => $step]);

            return ($updated?->rowCount() ?? 0) > 0;
        }

        $hashes = (array) json_decode((string) $row['recovery_codes'], true);
        $hash   = self::hashRecoveryCode($code);
        if (! in_array($hash, $hashes, true)) {
            return false;
        }

        $left = array_values(array_diff($hashes, [$hash]));
        Db::update('user_two_factor', ['recovery_codes' => json_encode($left)], ['user_id' => $user->id]);
        Events::record($user, 'recovery_code_used', ['left' => count($left)]);

        return true;
    }

    /**
     * New recovery codes and their hashes.
     *
     * @return array{string[], string[]}
     */
    private static function createRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES; $i++) {
            $code    = bin2hex(random_bytes(5));
            $codes[] = substr($code, 0, 5) . '-' . substr($code, 5);
        }

        return [$codes, array_map(self::hashRecoveryCode(...), $codes)];
    }

    /**
     * Keyed hash of a recovery code: a stolen table alone gives nothing to guess against.
     *
     * @param string $code
     * @return string
     */
    private static function hashRecoveryCode(string $code): string
    {
        return hash_hmac('sha256', strtolower(str_replace(['-', ' '], '', $code)), self::key());
    }

    /**
     * Encrypt the secret with AES-256-GCM.
     *
     * @param string $secret
     * @return string Base64 of the IV, the tag and the ciphertext.
     */
    private static function encrypt(string $secret): string
    {
        $iv     = random_bytes(12);
        $cipher = (string) openssl_encrypt($secret, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $cipher);
    }

    /**
     * Decrypt the secret.
     *
     * @param string $encrypted
     * @return string Empty if the key changed or the value is damaged.
     */
    private static function decrypt(string $encrypted): string
    {
        $data = (string) base64_decode($encrypted, true);
        if (strlen($data) < 28) {
            return '';
        }

        [$iv, $tag, $cipher] = [substr($data, 0, 12), substr($data, 12, 16), substr($data, 28)];

        return (string) openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
    }

    /**
     * Key of the secrets and codes, derived from the site key.
     *
     * @return string
     */
    private static function key(): string
    {
        return hash('sha256', 'two-factor|' . (defined('EX_KEYS') ? EX_KEYS['auth'] : ''), true);
    }

    /**
     * Start the session of the request if needed.
     *
     * @return void
     */
    private static function startSession(): void
    {
        if (! Session::isStarted()) {
            Session::start();
        }
    }
}
