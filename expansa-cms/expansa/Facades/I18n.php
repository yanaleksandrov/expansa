<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Patterns\Facade;

/**
 * I18n Facade class provides static methods for internationalization and localization,
 * including string translation, conditional translations, and language configuration.
 *
 * @method static void   t(string $string, mixed ...$args)
 * @method static string _t(string $string, mixed ...$args)
 * @method static void   t_attr(string $string, mixed ...$args)
 * @method static string _t_attr(string $string, mixed ...$args)
 * @method static void   c(bool $condition, string $ifString, string $elseString = '')
 * @method static string _c(bool $condition, string $ifString, string $elseString = '')
 * @method static void   c_attr(bool $condition, string $ifString, string $elseString = '')
 * @method static string _c_attr(bool $condition, string $ifString, string $elseString = '')
 * @method static void   configure(array $routes, string $pattern)
 * @method static string locale(string $default = 'en-US')
 * @method static array  getLanguage(string $value, string $getBy = 'locale')
 * @method static array  getLanguagesOptions()
 * @method static array  getLanguages()
 */
class I18n extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Translation\Translator';
    }
}
