<?php

declare(strict_types=1);

namespace App\Support;

use Expansa\Support\Arr;

/**
 * Secrets kept in the database encrypted with AES-256-GCM by a key derived from the site key
 * `EX_KEYS['auth']` and a purpose: a database dump alone gives neither SMTP passwords nor client
 * secrets. Changing the site key makes the saved secrets unreadable, they have to be entered again.
 *
 * Settings forms never show a saved secret back: `keep()` leaves it as it is when its field comes empty.
 */
final class Secrets
{
    /**
     * Encrypt a value.
     *
     * @param string $value
     * @param string $purpose Separates the keys of unrelated secrets.
     * @return string Base64 of the IV, the tag and the ciphertext; empty for an empty value.
     */
    public static function encrypt(string $value, string $purpose = 'settings'): string
    {
        if ($value === '') {
            return '';
        }

        $iv     = random_bytes(12);
        $cipher = (string) openssl_encrypt($value, 'aes-256-gcm', self::key($purpose), OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $cipher);
    }

    /**
     * Decrypt a value of encrypt().
     *
     * @param string $encrypted
     * @param string $purpose
     * @return string Empty if the site key changed or the value is damaged.
     */
    public static function decrypt(string $encrypted, string $purpose = 'settings'): string
    {
        $data = (string) base64_decode($encrypted, true);
        if (strlen($data) < 29) {
            return '';
        }

        [$iv, $tag, $cipher] = [substr($data, 0, 12), substr($data, 12, 16), substr($data, 28)];

        return (string) openssl_decrypt($cipher, 'aes-256-gcm', self::key($purpose), OPENSSL_RAW_DATA, $iv, $tag);
    }

    /**
     * Encrypt the secrets of a submitted settings group; an empty field keeps the saved value.
     *
     * @param array<string, mixed> $values Submitted group.
     * @param array<string, mixed> $saved  Saved group, its secrets already encrypted.
     * @param string[]             $paths  Dot paths of the secrets, e.g. `dkim.private`.
     * @return array<string, mixed>
     */
    public static function keep(array $values, array $saved, array $paths): array
    {
        foreach ($paths as $path) {
            $value = trim((string) Arr::get($values, $path, ''));
            Arr::set($values, $path, $value !== '' ? self::encrypt($value) : (string) Arr::get($saved, $path, ''));
        }

        return $values;
    }

    /**
     * Key of a purpose.
     *
     * @param string $purpose
     * @return string Raw 32 bytes.
     */
    public static function key(string $purpose): string
    {
        return hash('sha256', "$purpose|" . (defined('EX_KEYS') ? EX_KEYS['auth'] : ''), true);
    }
}
