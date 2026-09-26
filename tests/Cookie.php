<?php

declare(strict_types=1);

use Expansa\Cookie\Cookie;
use Expansa\Cookie\Exceptions\CookieException;
use Expansa\Cookie\SameSite;

require __DIR__ . '/bootstrap.php';

$cookie = new Cookie('session', 'a b', path: '', secure: true, httpOnly: true, sameSite: SameSite::Lax);
check('header value', (string) $cookie === 'session=a%20b; path=/; secure; httponly; samesite=lax');

$expires = time() + 60;
$cookie  = new Cookie('id', '0', $expires, '/app', 'example.com');
check('"0" is a value, not a deletion', str_starts_with((string) $cookie, 'id=0; expires='));
check('max age', $cookie->maxAge >= 59 && $cookie->maxAge <= 60);
check('path and domain', str_ends_with((string) $cookie, '; path=/app; domain=example.com'));

check('empty value deletes the cookie', str_starts_with((string) new Cookie('id'), 'id=deleted; expires=') && str_contains((string) new Cookie('id'), 'Max-Age=0'));
check('negative expiry becomes a session cookie', new Cookie('id', 'x', -5)->expires === 0);
check('illegal name', throws(fn () => new Cookie('a b'), CookieException::class) && throws(fn () => new Cookie('a[b]'), CookieException::class));

$_COOKIE['theme'] = 'dark';
check('get reads the request cookie', Cookie::get('theme') === 'dark' && Cookie::get('missing', 'light') === 'light');

$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
check('secure request behind a proxy', Cookie::isSecureRequest());

exit($failures ? 1 : 0);
