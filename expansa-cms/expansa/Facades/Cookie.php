<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Cookie\Cookie as CookieObject;
use Expansa\Cookie\CookieJar;
use Expansa\Cookie\Enums\SameSite;
use Expansa\Patterns\Facade;

/**
 * @method static void           configure(string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, SameSite $sameSite = SameSite::Lax)
 * @method static CookieObject   create(string $name, string $value, int $minutes = 0, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?SameSite $sameSite = null)
 * @method static CookieObject   createForever(string $name, string $value, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?SameSite $sameSite = null)
 * @method static CookieObject   createExpired(string $name, ?string $path = null, ?string $domain = null)
 * @method static void           queue(CookieObject|string $cookie, string $value = '', int $minutes = 0, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?SameSite $sameSite = null)
 * @method static bool           hasQueued(string $name, ?string $path = null)
 * @method static ?CookieObject  getQueued(string $name, ?string $path = null)
 * @method static CookieObject[] getQueue()
 * @method static void           forget(string $name, ?string $path = null)
 * @method static void           expire(string $name, ?string $path = null, ?string $domain = null)
 * @method static void           flush()
 */
class Cookie extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return CookieJar::class;
    }
}
