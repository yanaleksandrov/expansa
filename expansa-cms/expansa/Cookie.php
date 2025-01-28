<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Cookie\Cookie as CookieService;
use Expansa\Patterns\Facade;

/**
 * @method static CookieService make(string $name, string $value, int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null)
 * @method static CookieService forever(string $name, string $value, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null)
 * @method static CookieService forget($name, $path = null, $domain = null)
 * @method static bool   hasQueued(string $name, string $path = null)
 * @method static void   queue(CookieService|string $cookie, string $value = '', int $minutes = 0, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = true, string $sameSite = null)
 * @method static mixed  queued(string $name, mixed $default = null, string $path = null)
 * @method static void   unqueue(string $name, string $path = null)
 * @method static void   expire(string $name, string $path = null, string $domain = null)
 * @method static array  getQueuedCookies()
 * @method static void   flushQueuedCookies()
 * @method static string getPath()
 * @method static string getDomain()
 * @method static bool   getSecure()
 * @method static string getSameSite()
 */
class Cookie extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Cookie\CookieJar';
    }
}
