<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;

/**
 * Translations of Expansa\Translation\Manager: placeholders, plural forms, Markdown, locale and languages.
 *
 * @method static void   configure(array $routes, string $pattern, string $overrides = '', ?Closure $languages = null, ?Closure $locale = null)
 * @method static string translate(string $string, mixed ...$args)
 * @method static string translatePlural(string $forms, int $count, mixed ...$args)
 * @method static string translateAttribute(string $string, mixed ...$args)
 * @method static string translateAttributePlural(string $forms, int $count, mixed ...$args)
 * @method static string translateIf(bool $condition, string $ifString, string $elseString = '')
 * @method static string translateAttributeIf(bool $condition, string $ifString, string $elseString = '')
 * @method static string locale(string $default = 'en-US')
 * @method static array  language(string $value, string $getBy = 'locale')
 * @method static array  languageOptions()
 */
class I18n extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Translation\Manager::class;
    }
}
