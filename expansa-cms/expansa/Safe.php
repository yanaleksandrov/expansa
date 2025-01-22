<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Facades\Facade;
use Expansa\Security\Sanitizer;

/**
 * @method static Sanitizer data(array $fields, array $rules, array $extensions = [])
 * @method static string exist(mixed $value, ?string $comparisonValue)
 * @method static string string(mixed $value)
 * @method static array  array(mixed $value)
 * @method static int    int(mixed $value)
 * @method static int    absint(mixed $value)
 * @method static float  float(mixed $value)
 * @method static bool   bool(mixed $value)
 * @method static string json(mixed $value)
 * @method static string datetime(mixed $value)
 * @method static string markup(mixed $value)
 * @method static string html(mixed $value)
 * @method static string attribute(mixed $value)
 * @method static string attributes(mixed $value)
 * @method static float  price(mixed $value)
 * @method static string text(mixed $value, bool $keepNewLines = false)
 * @method static string textarea(mixed $value)
 * @method static string id(mixed $value)
 * @method static string name(mixed $value)
 * @method static string locale(mixed $value)
 * @method static string tag(mixed $value)
 * @method static string prop(mixed $value)
 * @method static string dot(mixed $value)
 * @method static string trim(mixed $value)
 * @method static string uppercase(mixed $value)
 * @method static string lowercase(mixed $value)
 * @method static string capitalize(mixed $value)
 * @method static string ucfirst(mixed $value)
 * @method static string pascalcase(mixed $value)
 * @method static string camelcase(mixed $value)
 * @method static string snakecase(mixed $value)
 * @method static string kebabcase(mixed $value)
 * @method static string flatcase(mixed $value)
 * @method static string hash(mixed $value)
 * @method static string email(mixed $value)
 * @method static string emailAntiSpam(mixed $value)
 * @method static string url(mixed $value)
 * @method static string href(mixed $value)
 * @method static string path(mixed $value)
 * @method static string class(mixed $value)
 * @method static string whitespace(mixed $value)
 * @method static string tags(mixed $value)
 * @method static string mime(mixed $value)
 * @method static string hex(mixed $value)
 * @method static string slug(string $value)
 * @method static string login(string $value)
 * @method static string accents(string $value)
 * @method static string filename(string $value)
 * @method static string tablename(string $value)
 */
class Safe extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return '\Expansa\Security\Sanitizer';
    }
}
