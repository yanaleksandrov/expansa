<?php

declare(strict_types=1);

namespace Expansa\Auth;

/**
 * Time-based one-time passwords (RFC 6238, HMAC-SHA1, 6 digits, 30 seconds) of authenticator apps:
 * a shared secret, a link to add it to an app and the check of a code. A code is accepted within
 * one step of clock difference and only once: verify() returns its time step, keep it and pass it
 * back, so a code seen by somebody else can't be replayed.
 *
 * ```php
 * $secret = Totp::createSecret();                          // keep it, encrypted
 * $link   = Totp::getUri($secret, 'admin', 'Example');     // show as a QR code
 * $step   = Totp::verify($secret, $code, $lastStep);       // null for a wrong or used code
 * ```
 */
final class Totp
{
    /**
     * Seconds a code lives.
     */
    public const int PERIOD = 30;

    /**
     * Digits of a code.
     */
    public const int DIGITS = 6;

    /**
     * Alphabet of Base32 (RFC 4648), the format apps take secrets in.
     */
    private const string BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Create a random 160-bit secret.
     *
     * @return string Base32 without padding.
     */
    public static function createSecret(): string
    {
        $bits = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $chunk) => self::BASE32[bindec($chunk)], str_split($bits, 5)));
    }

    /**
     * Link that adds the secret to an authenticator app, usually shown as a QR code.
     *
     * @param string $secret  Base32 secret.
     * @param string $account Shown in the app, e.g. the login.
     * @param string $issuer  Shown in the app, e.g. the site name.
     * @return string
     */
    public static function getUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account) . '?' . http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Check a code within one step of clock difference.
     *
     * @param string   $secret   Base32 secret.
     * @param string   $code     Code typed by the user, spaces are ignored.
     * @param int      $lastStep Time step of the last accepted code: it and the earlier ones are refused.
     * @param int|null $time     Unix time, now by default.
     * @return int|null Time step of the code to keep as the new $lastStep, null if the code is wrong or used.
     */
    public static function verify(string $secret, string $code, int $lastStep = 0, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);
        $key  = self::decode($secret);
        if ($key === '' || ! preg_match('/^\d{' . self::DIGITS . '}$/', (string) $code)) {
            return null;
        }

        $current = intdiv($time ?? time(), self::PERIOD);
        foreach ([$current, $current - 1, $current + 1] as $step) {
            if ($step > $lastStep && hash_equals(self::code($key, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Code of a time step (HOTP of RFC 4226).
     *
     * @param string $key  Raw secret.
     * @param int    $step
     * @return string
     */
    private static function code(string $key, int $step): string
    {
        $hash   = hash_hmac('sha1', pack('J', $step), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value  = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base32 secret.
     *
     * @param string $secret
     * @return string Raw bytes, empty if the secret is not Base32.
     */
    private static function decode(string $secret): string
    {
        $secret = strtoupper(rtrim(str_replace(' ', '', $secret), '='));
        $bits   = '';

        foreach (str_split($secret) as $char) {
            $value = strpos(self::BASE32, $char);
            if ($value === false) {
                return '';
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
