<?php

declare(strict_types=1);

use Expansa\Facades\Safe;
use Expansa\Security\Csrf\Contracts\SessionProvider;
use Expansa\Security\Csrf\Csrf;
use Expansa\Security\Exceptions\InvalidCsrfTokenException;

// run: php tests/Security.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';
require_once EX_PATH . 'expansa/functions.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

function rejects(callable $callback): bool
{
    try {
        $callback();
    } catch (InvalidCsrfTokenException) {
        return true;
    }

    return false;
}

// sanitizer: markup can't escape into attributes, links or tags
check('attribute() escapes quotes', ! str_contains(Safe::attribute('" onmouseover="alert(1)'), '"'));
check('html() escapes tags', ! str_contains(Safe::html('<script>alert(1)</script>'), '<'));
check('text() strips tags, closing ones too', Safe::text('<b>bold</b> & more') === 'bold & more');
check('text() keeps a lone "<"', Safe::text('5<6 and a < b') === '5&lt;6 and a &lt; b');
check('tags() removes every tag with its attributes', Safe::tags('<p>x</p><img src=x onerror=1>') === 'x');
check('markup() drops event handlers and javascript: links', ! preg_match('/onclick|javascript:/i', Safe::markup('<a href="javascript:x" onclick="y">l</a>')));

foreach (['javascript:alert(1)', ' JavaScript:alert(1)', 'data:text/html,x', 'vbscript:x'] as $url) {
    check("url() refuses $url", Safe::url($url) === '' && Safe::href($url) === '');
}
foreach (['https://example.com/a?b=1', '/relative/path', 'http://127.0.0.1:8099', 'mailto:a@b.co'] as $url) {
    check("url() keeps $url", Safe::url($url) === $url);
}

check('filename() can\'t climb out of the directory', ! str_contains(Safe::filename('../../etc/passwd'), '/') && ! str_contains(Safe::filename('..\\..\\x'), '\\'));
check('slug() keeps letters of any script', Safe::slug('Привет, мир!') === 'привет-мир' && Safe::slug('你好，世界') === '你好-世界' && Safe::slug('नमस्ते दुनिया') === 'नमस्ते-दुनिया');
check('slug() of Latin text is unchanged', Safe::slug('Hello World!') === 'hello-world' && Safe::slug('Version 2.0') === 'version-2-0' && Safe::slug('foo_bar--baz') === 'foo_bar-baz');
check('slug() of an encoded URL segment equals its text', Safe::slug('%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82') === 'привет');
check('slug() drops tags, entities and symbols', Safe::slug('<b>Tom</b> &amp; Jerry 😀') === 'tom-jerry');
check('tablename() keeps only identifier characters', preg_match('/^\w+$/', Safe::tablename('posts; DROP TABLE x')) === 1);
check('int() and absint() cast', Safe::int('12abc') === 12 && Safe::absint('-5') === 5);
check('data() applies rules and defaults', Safe::data(['a' => ' x ', 'n' => '7z'], ['a' => 'trim', 'n' => 'int', 'd' => 'trim:def'])->apply() === ['a' => 'x', 'n' => 7, 'd' => 'def']);

// translations: :name values are data and get escaped, %s is left for markup around the text
check('t() escapes a :name value', t('Hello, :name', '<img src=x onerror=alert(1)>') === 'Hello, &lt;img src=x onerror=alert(1)&gt;');
check('t() keeps the case rules of ::Name', t('Hi, ::Name', 'john') === 'Hi, John');
check('t() leaves %s for markup', t('%sLearn more%s', '<a href="/x">', '</a>') === '<a href="/x">Learn more</a>');
check('t_attr() escapes once', t_attr('Tom & Jerry') === 'Tom &amp; Jerry' && t_attr('v :n', '"x"') === 'v &quot;x&quot;');

// CSRF: a token is valid only as issued, and only for the client it was issued to
$store = new class implements SessionProvider {
    private array $values = [];

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }
};

$_SERVER['REMOTE_ADDR']     = '10.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'tests';

$csrf  = new Csrf($store);
$token = $csrf->generate('form');

check('a freshly issued token passes', ! rejects(fn () => $csrf->check('form', $token, multiple: true)));
check('an empty token is rejected', rejects(fn () => $csrf->check('form', '')));
check('a forged token is rejected', rejects(fn () => $csrf->check('form', base64_encode(str_repeat('x', 60)))));
check('a token of another form is rejected', rejects(fn () => $csrf->check('other', $token)));

$_SERVER['REMOTE_ADDR'] = '10.0.0.2';
check('a token used from another client is rejected', rejects(fn () => $csrf->check('form', $token)));

exit($failures > 0 ? 1 : 0);
