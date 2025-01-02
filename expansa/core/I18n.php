<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;

/**
 * @method static void t(string $string)
 * @method static string _t(string $string)
 * @method static void t_attr(string $string)
 * @method static string _t_attr(string $string)
 * @method static void f(string $string, mixed ...$args)
 * @method static string _f(string $string, mixed ...$args)
 * @method static void f_attr(string $string, mixed ...$args)
 * @method static string _f_attr(string $string, mixed ...$args)
 * @method static void c(bool $condition, string $ifString, string $elseString = '')
 * @method static string _c(bool $condition, string $ifString, string $elseString = '')
 * @method static void c_attr(bool $condition, string $ifString, string $elseString = '')
 * @method static string _c_attr(bool $condition, string $ifString, string $elseString = '')
 * @method static void configure(array $routes, string $pattern)
 * @method static string locale(string $default = 'en-US')
 * @method static array getLanguage(string $value, string $getBy = 'locale')
 * @method static array getLanguagesOptions()
 * @method static array getLanguages()
 */
class I18n extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Translation\Translator';
    }
}
