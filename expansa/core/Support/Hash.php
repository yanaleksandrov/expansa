<?php

declare(strict_types=1);

namespace Expansa\Support;

use Exception;
use Random\RandomException;

/**
 * The Hash Class.
 */
class Hash
{
    /**
     * Generates a random password drawn from the defined set of characters.
     * TODO: password_hash is slowly 10-20 times then md5.
     *
     * @param int  $length            Optional. The length of password to generate. Default 12.
     * @param bool $specialChars      Optional. Whether to include standard special characters.
     *                                Default true.
     * @param bool $extraSpecialChars Optional. Whether to include other special characters.
     *                                Used when generating secret keys and salts. Default false.
     * @return string The random password
     * @throws RandomException
     */
    public static function generate(int $length = 12, bool $specialChars = true, bool $extraSpecialChars = false): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        if ($specialChars) {
            $chars .= '!@#$%^&*()';
        }

        if ($extraSpecialChars) {
            $chars .= '-_[]{}<>~`+=,.;:/?|';
        }

        $password = '';
        for ($i = 0; $i < $length; ++$i) {
            $password .= substr($chars, random_int(0, strlen($chars) - 1), 1);
        }

        return $password;
    }

    /**
     * Создает хеш пароля.
     *
     * @throws Exception
     */
    public static function make(string $password, array $options = []): string
    {
        $algorithm = $options['hash_algorithm'] ?? PASSWORD_DEFAULT;

        $hashedPassword = password_hash($password, $algorithm);

        if ($hashedPassword === false) {
            throw new Exception('Не удалось создать хеш пароля.');
        }

        return $hashedPassword;
    }

    /**
     * Generate unique string from existing data.
     */
    public static function form(string $string, int $length = 6): string
    {
        return substr(preg_replace('/[^a-z]/', '', hash('sha256', $string)), 0, $length);
    }

    /**
     * Проверяет, совпадает ли введенный пароль с хешем.
     */
    public static function check(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Проверяет, нужно ли повторно хешировать пароль с использованием других опций.
     */
    public static function needsRehash(string $hashedPassword, array $options = []): bool
    {
        return password_needs_rehash($hashedPassword, $options['hash_algorithm'] ?? PASSWORD_DEFAULT, $options);
    }

    /**
     * Возвращает информацию о хеше пароля.
     *
     * @param string $hashedPassword
     * @return array
     */
    public static function info(string $hashedPassword): array
    {
        return password_get_info($hashedPassword);
    }

    /**
     * Устанавливает количество раундов для хеширования.
     *
     * @throws Exception
     */
    public static function setRounds(int $rounds): void
    {
        if (! defined('PASSWORD_ARGON2ID')) {
            throw new Exception('Алгоритм Argon2ID не поддерживается.');
        }

        $options = [
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost'   => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads'     => PASSWORD_ARGON2_DEFAULT_THREADS,
        ];

        $options['time_cost'] = $rounds;

        password_hash('', PASSWORD_ARGON2ID, $options);
    }
}
