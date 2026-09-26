<?php

declare(strict_types=1);

namespace Expansa\Support;

/**
 * Random passwords and keys.
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
}
