<?php

declare(strict_types=1);

namespace Expansa\Support;

use RuntimeException;

/**
 * Random passwords, password hashes and short fingerprints of strings.
 *
 * @package Expansa\Support
 */
final class Hash
{
    private const string CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private const string SPECIAL_CHARS = '!@#$%^&*()';

    private const string EXTRA_SPECIAL_CHARS = '-_[]{}<>~`+=,.;:/?|';

    /**
     * Generate a cryptographically secure random password.
     *
     * @param int  $length
     * @param bool $specialChars      Include `!@#$%^&*()`.
     * @param bool $extraSpecialChars Include other punctuation, for secret keys and salts.
     * @return string
     */
    public static function generate(int $length = 12, bool $specialChars = true, bool $extraSpecialChars = false): string
    {
        $chars = self::CHARS . ($specialChars ? self::SPECIAL_CHARS : '') . ($extraSpecialChars ? self::EXTRA_SPECIAL_CHARS : '');
        $max   = strlen($chars) - 1;

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }

        return $password;
    }

    /**
     * Hash a password with password_hash().
     *
     * @param string $password
     * @param array  $options `hash_algorithm` (PASSWORD_DEFAULT by default) and the password_hash() options.
     * @return string
     */
    public static function password(string $password, array $options = []): string
    {
        return password_hash($password, $options['hash_algorithm'] ?? PASSWORD_DEFAULT, $options);
    }

    /**
     * Get a short fingerprint of a string: the letters of its SHA-256 hash.
     *
     * @param string $string
     * @param int    $length
     * @return string
     */
    public static function fingerprint(string $string, int $length = 6): string
    {
        return substr(preg_replace('/[^a-z]/', '', hash('sha256', $string)), 0, $length);
    }

    /**
     * Check a password against its hash.
     *
     * @param string $password
     * @param string $hashedPassword
     * @return bool
     */
    public static function check(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Check if a hash was made with other options and should be made again.
     *
     * @param string $hashedPassword
     * @param array  $options Same as for password().
     * @return bool
     */
    public static function needsRehash(string $hashedPassword, array $options = []): bool
    {
        return password_needs_rehash($hashedPassword, $options['hash_algorithm'] ?? PASSWORD_DEFAULT, $options);
    }

    /**
     * Get the algorithm and options of a hash.
     *
     * @param string $hashedPassword
     * @return array{algo: string|null, algoName: string, options: array}
     */
    public static function info(string $hashedPassword): array
    {
        return password_get_info($hashedPassword);
    }

    /**
     * Hash an empty password with Argon2id and the given time cost, e.g. to check that it is supported.
     *
     * @param int $rounds Argon2id time cost.
     * @return void
     * @throws RuntimeException If PHP is built without Argon2id.
     */
    public static function setRounds(int $rounds): void
    {
        if (! defined('PASSWORD_ARGON2ID')) {
            throw new RuntimeException('The Argon2id algorithm is not supported.');
        }

        password_hash('', PASSWORD_ARGON2ID, [
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost'   => $rounds,
            'threads'     => PASSWORD_ARGON2_DEFAULT_THREADS,
        ]);
    }
}
