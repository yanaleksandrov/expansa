<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Auth\Contracts\Identity;
use Expansa\Auth\Contracts\Provider;
use Expansa\Auth\Contracts\Sessions;
use Expansa\Patterns\Facade;

/**
 * Auth facade: the signed-in user and sign-in providers of Expansa\Auth\Manager.
 *
 * @method static void          configure(?Closure $find = null, string $key = '', ?Closure $read = null, ?Closure $write = null, int $lifetime = 172800, int $rememberLifetime = 1209600, array|Closure $providers = [], ?Closure $transport = null, int $maxAttempts = 0, int $maxIpAttempts = 0, int $lockout = 900, ?Closure $readAttempts = null, ?Closure $writeAttempts = null, ?Sessions $sessions = null)
 * @method static \Expansa\Auth\Manager extend(string $driver, Closure $factory)
 * @method static Identity|null user()
 * @method static bool          isLoggedIn()
 * @method static bool          isTrustedDevice(string $identifier)
 * @method static bool          attempt(string $identifier, string $ip, Closure $check)
 * @method static void          login(Identity $user, bool $remember = false)
 * @method static void          refresh(Identity $user)
 * @method static void          logout(bool $all = false)
 * @method static bool          switchAccount(string $identifier)
 * @method static Identity[]    getAccounts()
 * @method static string        getSessionId()
 * @method static Provider      provider(string $name)
 * @method static string[]      getProviders()
 */
class Auth extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Auth\Manager::class;
    }
}
