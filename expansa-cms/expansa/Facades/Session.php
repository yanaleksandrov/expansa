<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Patterns\Facade;
use Expansa\Session\Contracts\Flash;
use Expansa\Session\Contracts\Lifecycle;
use Expansa\Session\Contracts\Session as SessionContract;
use Expansa\Session\Manager;

/**
 * Session of the request, see Expansa\Session\Manager: `Session::get('user')`, `Session::getFlash()->add(...)`.
 *
 * @method static void    configure(string $driver = 'native', array $options = [])
 * @method static Manager extend(string $driver, Closure $factory)
 * @method static SessionContract&Lifecycle driver()
 * @method static Flash   getFlash()
 * @method static bool    isStarted()
 * @method static void    start()
 * @method static void    regenerateId()
 * @method static void    delete()
 * @method static void    save()
 * @method static mixed   get(string $key, mixed $default = null)
 * @method static array   all()
 * @method static void    set(string $key, mixed $value)
 * @method static void    setValues(array $values)
 * @method static bool    has(string $key)
 * @method static void    forget(string $key)
 * @method static void    flush()
 */
class Session extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return Manager::class;
    }
}
