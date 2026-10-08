<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/**
 * Password rules of sign-up, change and reset: a minimum length, not the login or email, and not
 * found in public breaches (Have I Been Pwned, k-anonymity: only the first 5 characters of the
 * SHA-1 leave the server). An unreachable breach service never blocks a password.
 */
final class Passwords
{
    /**
     * Shortest password accepted.
     */
    public const int MIN_LENGTH = 8;

    /**
     * Range endpoint of the breach service, followed by the 5-character SHA-1 prefix.
     */
    private const string RANGE_URL = 'https://api.pwnedpasswords.com/range/';

    /**
     * Seconds to wait for the breach service.
     */
    private const int TIMEOUT = 3;

    /**
     * Fetches a range: `fn (string $prefix): ?string` — the response body, null if unreachable; curl by default.
     */
    private static ?Closure $transport = null;

    /**
     * Replace the request to the breach service, e.g. by a fake in tests.
     *
     * @param Closure|null $transport `fn (string $prefix): ?string`, null restores curl.
     * @return void
     */
    public static function configure(?Closure $transport = null): void
    {
        self::$transport = $transport;
    }

    /**
     * Check a new password.
     *
     * @param string   $password
     * @param string[] $personal Login, email and names the password must not be.
     * @return string|null Why the password is refused, null if it is fine.
     */
    public static function check(string $password, array $personal = []): ?string
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return t('The password must be at least :count characters long.', self::MIN_LENGTH);
        }

        $lower = mb_strtolower($password);
        foreach ($personal as $value) {
            $value = mb_strtolower(trim($value));
            if ($value !== '' && ($lower === $value || $lower === strstr($value, '@', true))) {
                return t('The password must not be your login or email.');
            }
        }

        if (self::isBreached($password)) {
            return t('This password has appeared in a data breach. Choose another one.');
        }

        return null;
    }

    /**
     * Whether the breach service knows the password; false if it can't be reached.
     *
     * @param string $password
     * @return bool
     */
    private static function isBreached(string $password): bool
    {
        if ((defined('EX_AUTH') ? EX_AUTH['breached'] ?? true : true) === false) {
            return false;
        }

        $hash   = strtoupper(sha1($password));
        $body   = (self::$transport ?? self::fetch(...))(substr($hash, 0, 5));
        $suffix = substr($hash, 5);

        foreach (explode("\n", (string) $body) as $line) {
            [$candidate, $count] = array_pad(explode(':', trim($line)), 2, '0');
            if ($candidate === $suffix && (int) $count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch a range with padding, so the response size reveals nothing about the prefix.
     *
     * @param string $prefix
     * @return string|null
     */
    private static function fetch(string $prefix): ?string
    {
        if (! function_exists('curl_init')) {
            return null;
        }

        $curl = curl_init(self::RANGE_URL . $prefix);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => ['Add-Padding: true', 'User-Agent: Expansa'],
        ]);

        $body = curl_exec($curl);

        return is_string($body) && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200 ? $body : null;
    }
}
