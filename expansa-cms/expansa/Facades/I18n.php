<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;

/**
 * Translations of Expansa\Translation\Translator: placeholders, Markdown, locale and languages.
 *
 * @method static void   configure(array $routes, string $pattern, string $overrides = '', ?Closure $languages = null)
 * @method static string translate(string $string, mixed ...$args)
 * @method static string translateAttribute(string $string, mixed ...$args)
 * @method static string locale(string $default = 'en-US')
 * @method static array  language(string $value, string $getBy = 'locale')
 * @method static array  languageOptions()
 * @method static array  languages()
 */
class I18n extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Translation\Translator::class;
    }
}
