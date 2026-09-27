<?php

declare(strict_types=1);

use Expansa\Cookie\Cookie;
use Expansa\Cookie\CookieJar;
use Expansa\Cookie\Enums\SameSite;
use Expansa\Cookie\Exceptions\InvalidName;

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
check('illegal name', throws(fn () => new Cookie('a b'), InvalidName::class) && throws(fn () => new Cookie('a[b]'), InvalidName::class));

$jar = new CookieJar();
check('jar defaults', $jar->path === '/' && $jar->httpOnly && $jar->sameSite === SameSite::Lax);
$jar->configure(domain: 'example.com', secure: true, sameSite: SameSite::Strict);
check('configure replaces the defaults', $jar->domain === 'example.com' && $jar->secure && $jar->path === '/');

$created = $jar->create('id', '1', 10);
check('create applies the defaults', $created->domain === 'example.com' && $created->secure && $created->sameSite === SameSite::Strict);
check('create expiry in minutes', abs($created->expires - time() - 600) <= 1 && $jar->create('s', 'x')->expires === 0);
check('createForever lasts about a year', $jar->createForever('id', '1', httpOnly: false)->expires > time() + 399 * 86400);
check('createExpired deletes', str_starts_with((string) $jar->createExpired('id'), 'id=deleted'));

$jar->queue('a', '1');
$jar->queue('a', '2', path: '/app');
$jar->queue($created);
check('queue by name and path', $jar->hasQueued('a') && $jar->hasQueued('a', '/app') && ! $jar->hasQueued('a', '/x'));
check('getQueued', $jar->getQueued('a', '/')->value === '1' && $jar->getQueued('a')->value === '2' && $jar->getQueued('b') === null);
check('getQueue', array_map(fn (Cookie $c) => $c->name . $c->path, $jar->getQueue()) === ['a/', 'a/app', 'id/']);
$jar->forget('a', '/app');
check('forget one path', ! $jar->hasQueued('a', '/app') && $jar->hasQueued('a', '/'));
$jar->forget('a');
check('forget all paths', ! $jar->hasQueued('a'));
$jar->expire('id');
check('expire queues a deletion', $jar->getQueued('id')->value === '');
$jar->flush();
check('flush', $jar->getQueue() === []);

$_COOKIE['theme'] = 'dark';
check('get reads the request cookie', Cookie::get('theme') === 'dark' && Cookie::get('missing', 'light') === 'light');

$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
check('secure request behind a proxy', Cookie::isSecureRequest());

exit($failures ? 1 : 0);
