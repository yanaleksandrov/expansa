<?php

declare(strict_types=1);

namespace Expansa\Auth;

use Closure;
use Expansa\Auth\Contracts\Identity;
use Expansa\Auth\Contracts\Provider;
use Expansa\Auth\Contracts\Sessions;
use Expansa\Auth\Exceptions\ProviderNotFound;
use Expansa\Auth\Exceptions\TooManyAttempts;
use Expansa\Auth\Internal\Token;
use Expansa\Auth\Providers\GitHub;
use Expansa\Auth\Providers\OpenId;
use LogicException;

/**
 * Authentication: who is signed in, the Auth facade instance.
 *
 * The user is carried by a signed token (see Contracts\Identity), so nothing is stored on the server
 * and anonymous requests write nothing. How tokens travel (cookies) and how a user is found are given
 * to configure() as callbacks. A sign-in method — a password, a Passkey, a provider — only proves who
 * the user is and ends with login(). Without configuration everyone is a guest.
 *
 * Password guessing is limited by attempt() with device cookies (OWASP): a browser that signed in
 * before keeps its own counter, so an attacker locks out only unknown devices, never the owner.
 *
 * ```php
 * Auth::user();                              // ?Identity, resolved once per request
 * Auth::attempt($login, $ip, fn () => password_verify($password, $hash));
 * Auth::login($user, remember: true);
 * $url = Auth::provider('google')->redirect($state);
 * ```
 *
 * @package Expansa\Auth
 */
final class Manager
{
    /**
     * Cookie of the sign-in token.
     */
    public const string TOKEN_COOKIE = 'auth';

    /**
     * Cookie of the device token: the browser has signed in as this user before.
     */
    public const string DEVICE_COOKIE = 'device';

    /**
     * Cookie of the other signed-in accounts of the browser, see switchAccount().
     */
    public const string ACCOUNTS_COOKIE = 'accounts';

    /**
     * Accounts a browser may keep signed in at once, the current one included.
     */
    private const int MAX_ACCOUNTS = 5;

    /**
     * Device token lifetime in seconds, renewed on every sign-in.
     */
    private const int DEVICE_LIFETIME = 31536000;

    /**
     * Stamp of device tokens: unlike the user's stamp it survives a new password.
     */
    private const string DEVICE_STAMP = 'device';

    /**
     * Longest lockout in seconds.
     */
    private const int MAX_LOCKOUT = 86400;

    /**
     * Seconds without failures after which past lockouts are forgotten.
     */
    private const int DECAY = 86400;

    /**
     * Finds a user by identifier: `fn (string $identifier): ?Identity`.
     */
    private ?Closure $find = null;

    /**
     * Secret key the tokens are signed with.
     */
    private string $key = '';

    /**
     * Reads a cookie of the request: `fn (string $name): string`.
     */
    private ?Closure $read = null;

    /**
     * Sends a cookie: `fn (string $name, string $value, int $expires): void`, an empty value removes it.
     */
    private ?Closure $write = null;

    /**
     * Token lifetime in seconds.
     */
    private int $lifetime = 172800;

    /**
     * Token lifetime in seconds with "remember me".
     */
    private int $rememberLifetime = 1209600;

    /**
     * Provider configs by name, or a callback returning them on first use.
     *
     * @var array<string, array<string, mixed>>|Closure
     */
    private array|Closure $config = [];

    /**
     * Request sender of the providers, curl if null.
     */
    private ?Closure $transport = null;

    /**
     * Failed passwords allowed per device, or per login from unknown devices; 0 turns throttling off.
     */
    private int $maxAttempts = 0;

    /**
     * Failed passwords allowed per IP from unknown devices, 0 turns the IP limit off.
     */
    private int $maxIpAttempts = 0;

    /**
     * First lockout in seconds, each next one is twice as long; also the window failures are counted in.
     */
    private int $lockout = 900;

    /**
     * Reads the failed attempts of a key: `fn (string $key): ?array`.
     */
    private ?Closure $readAttempts = null;

    /**
     * Stores the failed attempts of a key: `fn (string $key, ?array $attempts, int $ttl): void`, null removes them.
     */
    private ?Closure $writeAttempts = null;

    /**
     * Server-side sign-in records, null keeps tokens stateless.
     */
    private ?Sessions $sessions = null;

    /**
     * Custom provider factories by driver.
     *
     * @var array<string, Closure>
     */
    private array $drivers = [];

    /**
     * Created providers by name.
     *
     * @var array<string, Provider>
     */
    private array $providers = [];

    /**
     * The signed-in user, resolved on the first user() call.
     */
    private ?Identity $user = null;

    /**
     * Whether the token of the request has been read.
     */
    private bool $resolved = false;

    /**
     * The token of the signed-in user, null for a guest.
     */
    private ?Token $token = null;

    /**
     * Other signed-in accounts of the browser, resolved on the first getAccounts() call.
     *
     * @var Identity[]|null
     */
    private ?array $accounts = null;

    /**
     * Tokens of the other accounts as the cookie holds them, read on first use and kept in step with writes.
     *
     * @var Token[]|null
     */
    private ?array $accountTokens = null;

    /**
     * Set the cookies, throttling and providers, the previous configuration and the user are dropped.
     *
     * @param Closure|null  $find             `fn (string $identifier): ?Identity`.
     * @param string        $key              Secret key; without it everyone is a guest.
     * @param Closure|null  $read             `fn (string $name): string` — a cookie of the request: TOKEN_COOKIE, DEVICE_COOKIE.
     * @param Closure|null  $write            `fn (string $name, string $value, int $expires): void` — `$expires` 0 keeps
     *                                        it for the browser session, an empty value removes it.
     * @param int           $lifetime         Token lifetime in seconds.
     * @param int           $rememberLifetime Token lifetime with "remember me".
     * @param array|Closure $providers        Name => `driver` (the name by default), `client_id`, `client_secret`,
     *                                        `redirect`, `scopes`, `issuer` for `openid`; a callback returns them
     *                                        on first use, e.g. with URLs from the database.
     * @param Closure|null  $transport        Request sender of the providers, curl by default.
     * @param int           $maxAttempts      Failed passwords per device, or per login from unknown devices; 0 — no limit.
     * @param int           $maxIpAttempts    Failed passwords per IP from unknown devices; 0 — no IP limit.
     * @param int           $lockout          First lockout in seconds, each next one doubles up to a day.
     * @param Closure|null  $readAttempts     `fn (string $key): ?array` — stored attempts, e.g. from a cache.
     * @param Closure|null  $writeAttempts    `fn (string $key, ?array $attempts, int $ttl): void` — null removes them.
     * @param Sessions|null $sessions         Server-side sign-in records, so a device can be signed out.
     * @return void
     */
    public function configure(
        ?Closure $find = null,
        string $key = '',
        ?Closure $read = null,
        ?Closure $write = null,
        int $lifetime = 172800,
        int $rememberLifetime = 1209600,
        array|Closure $providers = [],
        ?Closure $transport = null,
        int $maxAttempts = 0,
        int $maxIpAttempts = 0,
        int $lockout = 900,
        ?Closure $readAttempts = null,
        ?Closure $writeAttempts = null,
        ?Sessions $sessions = null,
    ): void {
        $this->find             = $find;
        $this->key              = $key;
        $this->read             = $read;
        $this->write            = $write;
        $this->lifetime         = $lifetime;
        $this->rememberLifetime = $rememberLifetime;
        $this->config           = $providers;
        $this->transport        = $transport;
        $this->maxAttempts      = $maxAttempts;
        $this->maxIpAttempts    = $maxIpAttempts;
        $this->lockout          = $lockout;
        $this->readAttempts     = $readAttempts;
        $this->writeAttempts    = $writeAttempts;
        $this->sessions         = $sessions;
        $this->providers        = [];
        $this->user             = null;
        $this->resolved         = false;
        $this->token            = null;
        $this->accounts         = null;
        $this->accountTokens    = null;
    }

    /**
     * Add a provider driver.
     *
     * @param string  $driver
     * @param Closure $factory `fn (array $config, string $name, ?Closure $transport): Provider`.
     * @return static
     */
    public function extend(string $driver, Closure $factory): static
    {
        $this->drivers[$driver] = $factory;

        return $this;
    }

    /**
     * Get the signed-in user. A token that is invalid, expired or of a removed user is removed.
     *
     * @return Identity|null Null for a guest.
     */
    public function user(): ?Identity
    {
        if (! $this->resolved) {
            $this->resolved = true;
            $this->user     = $this->resolve();
        }

        return $this->user;
    }

    /**
     * Check if a user is signed in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Check if this browser has signed in as the user before: it carries a valid device token.
     *
     * @param string $identifier
     * @return bool
     */
    public function isTrustedDevice(string $identifier): bool
    {
        return $this->getDeviceToken($identifier) !== '';
    }

    /**
     * Run a password check with throttling. A trusted device has its own counter; an unknown one shares
     * the counter of the login and of the IP. After the allowed failures a counter is locked for lockout
     * seconds, twice as long each next time; a success forgets the failures, except those of the IP.
     *
     * @param string  $identifier The user's identifier if found, otherwise what was typed.
     * @param string  $ip         Client IP, empty to skip the IP limit.
     * @param Closure $check      `fn (): bool` — whether the password is valid.
     * @return bool The result of the check.
     * @throws TooManyAttempts If a counter is locked; the check is not run.
     */
    public function attempt(string $identifier, string $ip, Closure $check): bool
    {
        if ($this->maxAttempts < 1 || $this->readAttempts === null || $this->writeAttempts === null) {
            return (bool) $check();
        }

        // a login or an IP never reaches the store, only hashes of them
        $device = $this->getDeviceToken($identifier);
        $limits = $device !== ''
            ? [hash('sha256', "device:$device") => $this->maxAttempts]
            : [hash('sha256', 'login:' . mb_strtolower($identifier)) => $this->maxAttempts];

        $ipKey = $device === '' && $ip !== '' && $this->maxIpAttempts > 0 ? hash('sha256', "ip:$ip") : '';
        if ($ipKey !== '') {
            $limits[$ipKey] = $this->maxIpAttempts;
        }

        $now     = time();
        $records = [];
        foreach (array_keys($limits) as $key) {
            $records[$key] = $this->readRecord($key, $now);
        }

        $wait = max(array_map(fn (array $record) => $record['until'] - $now, $records));
        if ($wait > 0) {
            throw new TooManyAttempts($wait);
        }

        if ($check()) {
            foreach ($records as $key => $record) {
                if ($key !== $ipKey && $record['last'] > 0) {
                    ($this->writeAttempts)($key, null, 0);
                }
            }

            return true;
        }

        foreach ($records as $key => $record) {
            $this->writeFailure($key, $record, $limits[$key], $now);
        }

        return false;
    }

    /**
     * Count a request and throw once a key made more than $maxAttempts within $window seconds, e.g. reset
     * emails per IP. Unlike attempt() every request counts, successful or not. Without a store it does nothing.
     *
     * @param string $key         What is limited, e.g. `"reset:$ip"`; only its hash reaches the store.
     * @param int    $maxAttempts Requests allowed in the window.
     * @param int    $window      Seconds since the first request of the window.
     * @return void
     * @throws TooManyAttempts If the key used up its requests.
     */
    public function limit(string $key, int $maxAttempts, int $window): void
    {
        if ($this->readAttempts === null || $this->writeAttempts === null) {
            return;
        }

        $key    = hash('sha256', "limit:$key");
        $now    = time();
        $stored = ($this->readAttempts)($key);
        $until  = (int) ($stored['until'] ?? 0);
        $count  = $until > $now ? (int) ($stored['count'] ?? 0) : 0;

        if ($count >= $maxAttempts) {
            throw new TooManyAttempts($until - $now);
        }

        $until = $count > 0 ? $until : $now + $window;
        ($this->writeAttempts)($key, ['count' => $count + 1, 'until' => $until], $until - $now);
    }

    /**
     * Sign in a user whose identity is already proven: issue the token and trust the device.
     * The account signed in before stays in the browser, see switchAccount().
     *
     * @param Identity $user
     * @param bool     $remember Keep the token for rememberLifetime across browser restarts.
     * @return void
     * @throws LogicException If Auth is not configured.
     */
    public function login(Identity $user, bool $remember = false): void
    {
        $previous = $this->user()?->identifier !== $user->identifier ? $this->token : null;
        $others   = array_filter(
            $this->readAccounts(),
            fn (Token $token) => $token->identifier !== $user->identifier && $token->identifier !== $previous?->identifier,
        );

        $this->issue($user, time() + ($remember ? $this->rememberLifetime : $this->lifetime), null, $remember);
        $this->writeAccounts($previous !== null ? [$previous, ...$others] : $others);

        $expires = time() + self::DEVICE_LIFETIME;
        $device  = Token::sign($user->identifier, $expires, '', self::DEVICE_STAMP, $this->key);
        ($this->write)(self::DEVICE_COOKIE, $device, $expires);
    }

    /**
     * Make another signed-in account of the browser current; the current one moves to the others.
     *
     * @param string $identifier
     * @return bool False if the account is not signed in here or its token stopped working.
     */
    public function switchAccount(string $identifier): bool
    {
        $accounts = $this->readAccounts();
        $target   = array_find($accounts, fn (Token $token) => $token->identifier === $identifier);
        $others   = array_filter($accounts, fn (Token $token) => $token !== $target);
        $user     = $target !== null ? $this->verify($target) : null;

        if ($user === null) {
            $this->writeAccounts($others);

            return false;
        }

        $current = $this->user() !== null ? $this->token : null;

        $this->setCurrent($target, $user);
        $this->writeAccounts($current !== null ? [$current, ...$others] : $others);

        return true;
    }

    /**
     * Get the other signed-in accounts of the browser, the most recent first.
     *
     * @return Identity[]
     */
    public function getAccounts(): array
    {
        if ($this->accounts === null) {
            $current        = $this->user()?->identifier;
            $this->accounts = [];

            foreach ($this->readAccounts() as $token) {
                $user = $token->identifier !== $current ? $this->verify($token) : null;
                if ($user !== null) {
                    $this->accounts[] = $user;
                }
            }
        }

        return $this->accounts;
    }

    /**
     * Get the session ID of the signed-in user, e.g. to mark "this device" in a list of sessions.
     *
     * @return string Empty for a guest or without sessions.
     */
    public function getSessionId(): string
    {
        return $this->user() !== null ? $this->token->session : '';
    }

    /**
     * Re-issue the token after the stamp of the user changed, e.g. a new password: other devices
     * are signed out, this one stays with the same expiry and session. Does nothing for another user.
     *
     * @param Identity $user
     * @return void
     * @throws LogicException If Auth is not configured.
     */
    public function refresh(Identity $user): void
    {
        if ($this->user()?->identifier === $user->identifier) {
            $this->issue($user, $this->token->expires, $this->token->session, $this->isRemembered($this->token));
        }
    }

    /**
     * Sign out the current account, the next signed-in one of the browser becomes current;
     * with $all every account of the browser. The device stays trusted.
     *
     * @param bool $all
     * @return void
     */
    public function logout(bool $all = false): void
    {
        $this->user();

        $accounts = $this->readAccounts();
        foreach ($all ? [$this->token, ...$accounts] : [$this->token] as $token) {
            if ($token !== null && $token->session !== '') {
                $this->sessions?->delete($token->session);
            }
        }

        $this->forgetToken();

        if ($all) {
            $this->writeAccounts([]);

            return;
        }

        // the next account that still works takes over, broken ones are dropped on the way
        while ($accounts !== []) {
            $token = array_shift($accounts);
            $user  = $this->verify($token);

            if ($user !== null) {
                $this->setCurrent($token, $user);
                break;
            }
        }

        $this->writeAccounts($accounts);
    }

    /**
     * Get a configured provider, created on first use.
     *
     * @param string $name
     * @return Provider
     * @throws ProviderNotFound If the name is not configured or its driver is unknown.
     */
    public function provider(string $name): Provider
    {
        return $this->providers[$name] ??= $this->createProvider($name);
    }

    /**
     * Names of the configured providers, e.g. for the sign-in buttons.
     *
     * @return string[]
     */
    public function getProviders(): array
    {
        return array_keys($this->getConfig());
    }

    /**
     * Provider configs, the callback of configure() is called once.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getConfig(): array
    {
        return is_array($this->config) ? $this->config : $this->config = ($this->config)();
    }

    /**
     * Read the token of the request and find its user; a token that stopped working is removed.
     *
     * @return Identity|null
     */
    private function resolve(): ?Identity
    {
        $raw   = $this->read !== null ? (string) ($this->read)(self::TOKEN_COOKIE) : '';
        $token = $raw !== '' ? Token::parse($raw) : null;
        $user  = $token !== null ? $this->verify($token) : null;

        if ($user !== null) {
            $this->token = $token;

            return $user;
        }

        if ($raw !== '') {
            $this->forgetToken();
        }

        return null;
    }

    /**
     * Find the user of a token and check its signature and session.
     *
     * @param Token $token
     * @return Identity|null Null if the token stopped working.
     */
    private function verify(Token $token): ?Identity
    {
        if ($this->find === null || $this->key === '') {
            return null;
        }

        $user = ($this->find)($token->identifier);

        $isValid = $user instanceof Identity
            && $user->identifier === $token->identifier
            && $token->isSignedWith($user->stamp, $this->key)
            // with sessions every token needs a live one, a stateless token is not enough
            && ($this->sessions === null || ($token->session !== '' && $this->sessions->isActive($token->session, $user)));

        return $isValid ? $user : null;
    }

    /**
     * Make a verified token current.
     *
     * @param Token    $token
     * @param Identity $user
     * @return void
     */
    private function setCurrent(Token $token, Identity $user): void
    {
        ($this->write)(self::TOKEN_COOKIE, $token->raw, $this->isRemembered($token) ? $token->expires : 0);

        $this->user     = $user;
        $this->token    = $token;
        $this->resolved = true;
        $this->accounts = null;
    }

    /**
     * Remove the current token.
     *
     * @return void
     */
    private function forgetToken(): void
    {
        if ($this->write !== null) {
            ($this->write)(self::TOKEN_COOKIE, '', 0);
        }

        $this->user     = null;
        $this->token    = null;
        $this->resolved = true;
        $this->accounts = null;
    }

    /**
     * Whether a token was issued with "remember me": it outlives the normal lifetime.
     *
     * @param Token $token
     * @return bool
     */
    private function isRemembered(Token $token): bool
    {
        return $token->expires - time() > $this->lifetime;
    }

    /**
     * Tokens of the other signed-in accounts, the most recent first; expired and malformed ones are skipped.
     *
     * @return Token[]
     */
    private function readAccounts(): array
    {
        if ($this->accountTokens === null) {
            $raw    = $this->read !== null ? (string) ($this->read)(self::ACCOUNTS_COOKIE) : '';
            $values = $raw !== '' ? json_decode($raw, true) : null;

            $this->accountTokens = array_values(array_filter(array_map(
                fn (mixed $value) => is_string($value) ? Token::parse($value) : null,
                is_array($values) ? array_slice($values, 0, self::MAX_ACCOUNTS - 1) : [],
            )));
        }

        return $this->accountTokens;
    }

    /**
     * Store the tokens of the other accounts. The cookie outlives the browser session only when every
     * token was remembered: a session token must not survive closing the browser on a shared computer.
     *
     * @param Token[] $tokens
     * @return void
     */
    private function writeAccounts(array $tokens): void
    {
        $tokens = array_values(array_slice($tokens, 0, self::MAX_ACCOUNTS - 1));
        $raw    = array_map(fn (Token $token) => $token->raw, $tokens);
        if ($raw === array_map(fn (Token $token) => $token->raw, $this->readAccounts())) {
            return;
        }

        $this->accountTokens = $tokens;
        $this->accounts      = null;

        $isRemembered = $tokens !== [] && array_all($tokens, fn (Token $token) => $this->isRemembered($token));
        $expires      = $isRemembered ? max(array_map(fn (Token $token) => $token->expires, $tokens)) : 0;

        ($this->write)(self::ACCOUNTS_COOKIE, $tokens === [] ? '' : (string) json_encode($raw), $expires);
    }

    /**
     * Get the device token of the request if it was issued to the user.
     *
     * @param string $identifier
     * @return string Empty if there is none or it is invalid.
     */
    private function getDeviceToken(string $identifier): string
    {
        $raw   = $this->read !== null && $this->key !== '' ? (string) ($this->read)(self::DEVICE_COOKIE) : '';
        $token = $raw !== '' ? Token::parse($raw) : null;

        return $token?->identifier === $identifier && $token->isSignedWith(self::DEVICE_STAMP, $this->key) ? $raw : '';
    }

    /**
     * Read the failures of a key; past lockouts are forgotten after DECAY seconds without failures.
     *
     * @param string $key
     * @param int    $now
     * @return array{count: int, lockouts: int, until: int, last: int}
     */
    private function readRecord(string $key, int $now): array
    {
        $stored = ($this->readAttempts)($key);
        $record = [
            'count'    => (int) ($stored['count'] ?? 0),
            'lockouts' => (int) ($stored['lockouts'] ?? 0),
            'until'    => (int) ($stored['until'] ?? 0),
            'last'     => (int) ($stored['last'] ?? 0),
        ];

        return $now - max($record['until'], $record['last']) > self::DECAY
            ? ['count' => 0, 'lockouts' => 0, 'until' => 0, 'last' => 0]
            : $record;
    }

    /**
     * Count a failure; reaching the limit locks the key for lockout seconds, doubled for each past lockout.
     *
     * @param string                                                 $key
     * @param array{count: int, lockouts: int, until: int, last: int} $record
     * @param int                                                    $max
     * @param int                                                    $now
     * @return void
     */
    private function writeFailure(string $key, array $record, int $max, int $now): void
    {
        // failures further apart than the lockout don't add up
        $record['count'] = $now - $record['last'] > $this->lockout ? 1 : $record['count'] + 1;
        $record['last']  = $now;

        if ($record['count'] >= $max) {
            $record['until'] = $now + (int) min($this->lockout * 2 ** $record['lockouts'], self::MAX_LOCKOUT);
            $record['lockouts']++;
            $record['count'] = 0;
        }

        ($this->writeAttempts)($key, $record, max($record['until'], $now) - $now + self::DECAY);
    }

    /**
     * Sign and send a token.
     *
     * @param Identity $user
     * @param int      $expires
     * @param bool     $remember
     * @return void
     * @throws LogicException If Auth is not configured.
     */
    private function issue(Identity $user, int $expires, ?string $session, bool $remember): void
    {
        if ($this->write === null || $this->key === '') {
            throw new LogicException('Auth is not configured: a key and a write callback are required to sign in.');
        }

        $session ??= $this->sessions?->create($user, $expires) ?? '';
        if (! preg_match('/^[A-Za-z0-9]*$/', $session)) {
            throw new LogicException('A session ID may contain only letters and digits.');
        }

        $token = Token::parse(Token::sign($user->identifier, $expires, $session, $user->stamp, $this->key));
        ($this->write)(self::TOKEN_COOKIE, $token->raw, $remember ? $expires : 0);

        $this->user     = $user;
        $this->token    = $token;
        $this->resolved = true;
        $this->accounts = null;
    }

    /**
     * Create a provider from its config.
     *
     * @param string $name
     * @return Provider
     * @throws ProviderNotFound
     */
    private function createProvider(string $name): Provider
    {
        $config = $this->getConfig()[$name] ?? throw new ProviderNotFound("Sign-in provider [$name] is not configured.");
        $driver = (string) ($config['driver'] ?? $name);

        if (isset($this->drivers[$driver])) {
            return ($this->drivers[$driver])($config, $name, $this->transport);
        }

        $method = 'create' . ucfirst($driver) . 'Driver';
        if (! method_exists($this, $method)) {
            throw new ProviderNotFound("Sign-in provider driver [$driver] is not supported.");
        }

        return $this->$method($config, $name);
    }

    /**
     * Google: OpenID Connect with the issuer `https://accounts.google.com`.
     *
     * @param array<string, mixed> $config
     * @param string               $name
     * @return OpenId
     */
    private function createGoogleDriver(array $config, string $name): OpenId
    {
        return $this->createOpenidDriver(['issuer' => 'https://accounts.google.com'] + $config, $name);
    }

    /**
     * Any OpenID Connect provider by `issuer`.
     *
     * @param array<string, mixed> $config
     * @param string               $name
     * @return OpenId
     */
    private function createOpenidDriver(array $config, string $name): OpenId
    {
        return new OpenId(
            name: $name,
            clientId: (string) ($config['client_id'] ?? ''),
            clientSecret: (string) ($config['client_secret'] ?? ''),
            redirect: (string) ($config['redirect'] ?? ''),
            issuer: (string) ($config['issuer'] ?? ''),
            scopes: (array) ($config['scopes'] ?? []),
            transport: $this->transport,
        );
    }

    /**
     * GitHub OAuth app.
     *
     * @param array<string, mixed> $config
     * @param string               $name
     * @return GitHub
     */
    private function createGithubDriver(array $config, string $name): GitHub
    {
        return new GitHub(
            name: $name,
            clientId: (string) ($config['client_id'] ?? ''),
            clientSecret: (string) ($config['client_secret'] ?? ''),
            redirect: (string) ($config['redirect'] ?? ''),
            scopes: (array) ($config['scopes'] ?? []),
            transport: $this->transport,
        );
    }
}
