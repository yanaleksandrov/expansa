<?php

declare(strict_types=1);

use Expansa\Support\Arr;

// run: php tests/Arr.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

check('clean() drops empty values recursively', Arr::clean(['a' => 1, 'b' => '', 'c' => ['d' => null, 'e' => 2], 'f' => [[]]]) === ['a' => 1, 'c' => ['e' => 2]]);
check('exclude() drops listed keys', Arr::exclude(['a' => 1, 'b' => 2, 'c' => 3], ['b', 'x']) === ['a' => 1, 'c' => 3]);
check('extract() keeps listed keys', Arr::extract(['a' => 1, 'b' => 2, 'c' => 3], ['c', 'a']) === ['a' => 1, 'c' => 3]);

check('insert() places items after the key', Arr::insert(['a' => 1, 'c' => 3], 'a', ['b' => 2]) === ['a' => 1, 'b' => 2, 'c' => 3]);
check('insert() after the last key appends', Arr::insert(['a' => 1], 'a', ['b' => 2]) === ['a' => 1, 'b' => 2]);
check('insert() ignores a missing key', Arr::insert(['a' => 1], 'x', ['b' => 2]) === ['a' => 1]);
check('insert() finds an int key by a numeric string', Arr::insert([5 => 'a', 7 => 'c'], '5', ['b']) === ['a', 'b', 'c']);
check('insert() supports "dot" notation', Arr::insert(['menu' => ['a' => 1, 'c' => 3], 'z' => 0], 'menu.a', ['b' => 2]) === ['menu' => ['a' => 1, 'b' => 2, 'c' => 3], 'z' => 0]);
check('insert() goes deep with "dot" notation', Arr::insert(['x' => ['y' => ['a' => 1, 'c' => 3]]], 'x.y.a', ['b' => 2]) === ['x' => ['y' => ['a' => 1, 'b' => 2, 'c' => 3]]]);
check('insert() prefers a literal key with dots', Arr::insert(['a.b' => 1, 'a' => ['b' => 1]], 'a.b', ['n' => 2]) === ['a.b' => 1, 'n' => 2, 'a' => ['b' => 1]]);
check('insert() ignores a missing nested path', Arr::insert(['a' => ['b' => 1], 'c' => 'str'], 'c.b', ['n' => 2]) === ['a' => ['b' => 1], 'c' => 'str']);

check('sortByPattern() moves pattern keys first', Arr::sortByPattern(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], ['c', 'x', 'a']) === ['c' => 3, 'a' => 1, 'b' => 2, 'd' => 4]);
check('sortByPattern() keeps int keys', Arr::sortByPattern([10 => 'a', 20 => 'b'], [20]) === [20 => 'b', 10 => 'a']);

check('sort() orders by field', Arr::sort([['p' => 2], ['p' => 1]], 'p') === [['p' => 1], ['p' => 2]]);
check('sort() orders descending', Arr::sort([['p' => 1], ['p' => 2]], 'p', SORT_DESC) === [['p' => 2], ['p' => 1]]);
check('map() applies the callback to leaves', Arr::map(['a' => 1, 'b' => ['c' => 2]], fn ($v) => $v * 10) === ['a' => 10, 'b' => ['c' => 20]]);

$list = ['x' => ['t' => 'a', 'n' => 1], 'y' => ['t' => 'b', 'n' => 1], 'z' => (object) ['t' => 'a', 'n' => 2], 'w' => ['t' => null]];
check('filter() AND matches all arguments', array_keys(Arr::filter($list, ['t' => 'a', 'n' => 1])) === ['x']);
check('filter() OR matches any argument', array_keys(Arr::filter($list, ['t' => 'b', 'n' => 2], 'or')) === ['y', 'z']);
check('filter() NOT matches none', array_keys(Arr::filter($list, ['t' => 'a'], 'NOT')) === ['y', 'w']);
check('filter() matches a null array value', array_keys(Arr::filter($list, ['t' => null])) === ['w']);
check('filter() returns the list for an unknown operator', Arr::filter($list, ['t' => 'a'], 'XOR') === $list);

check('toHtmlAtts() renders attributes', Arr::toHtmlAtts(['type' => 'text', 'name' => 'a"b']) === ' type="text" name="a&quot;b"');
check('toHtmlAtts() trims and escapes values', Arr::toHtmlAtts(['class' => ' a b ', 'title' => "<i>&'"]) === ' class="a b" title="&lt;i&gt;&amp;&#039;"');
check('toHtmlAtts() renders scalars', Arr::toHtmlAtts(['tabindex' => 3, 'u-if' => true]) === ' tabindex="3" u-if="1"');
check('toHtmlAtts() renders boolean attributes by value', Arr::toHtmlAtts(['checked' => true, 'disabled' => false]) === ' checked');
check('toHtmlAtts() skips empty values without extra spaces', Arr::toHtmlAtts(['class' => '', 'id' => 'x', 'title' => null]) === ' id="x"');
check('toHtmlAtts() keeps an empty value attribute', Arr::toHtmlAtts(['value' => '']) === ' value=""');
check('toHtmlAtts() keeps empty u- attributes', Arr::toHtmlAtts(['u-text' => '']) === ' u-text');
check('toHtmlAtts() returns an empty string for nothing', Arr::toHtmlAtts(['' => 'x', 'class' => '']) === '');

check('dot() flattens nested arrays', Arr::dot(['a' => ['b' => ['c' => 1], 'd' => []], 'e' => 2]) === ['a.b.c' => 1, 'a.d' => [], 'e' => 2]);
check('dot() applies the prefix', Arr::dot(['a' => 1], 'p.') === ['p.a' => 1]);
check('dot() accepts a Traversable', Arr::dot(new ArrayIterator(['a' => ['b' => 1]])) === ['a.b' => 1]);
check('undot() expands keys', Arr::undot(['a.b' => 1, 'a.c' => 2, 'd' => 3]) === ['a' => ['b' => 1, 'c' => 2], 'd' => 3]);

$array = ['a' => ['b' => 1], 'c' => 'str'];
Arr::set($array, 'a.x.y', 2);
Arr::set($array, 'c.d', 3);
Arr::set($array, 'e', 4);
Arr::set($array, 0, 5);
check('set() writes by "dot" notation', $array === ['a' => ['b' => 1, 'x' => ['y' => 2]], 'c' => ['d' => 3], 'e' => 4, 0 => 5]);
check('set() returns the array that received the value', Arr::set($array, 'a.x.z', 6) === ['y' => 2, 'z' => 6]);
Arr::set($array, null, ['r']);
check('set() with a null key replaces the array', $array === ['r']);

$array = ['a' => ['b' => ['c' => 1, 'n' => null]], 'a.b' => 'literal', 's' => 'str', 3 => 'int'];
check('get() reads by "dot" notation', Arr::get($array, 'a.b.c') === 1);
check('get() prefers a literal key with dots', Arr::get($array, 'a.b') === 'literal');
check('get() returns a null value of an existing key', Arr::get(['k' => null], 'k', 'def') === null);
check('get() returns the default for a missing key', Arr::get($array, 'x', 'def') === 'def');
check('get() returns the default for a missing path', Arr::get($array, 'a.x.c', 'def') === 'def');
check('get() does not descend into a scalar', Arr::get($array, 's.x', 'def') === 'def');
check('get() reads int keys', Arr::get($array, 3) === 'int');
check('get() with a null key returns the array', Arr::get($array, null) === $array);

exit($failures > 0 ? 1 : 0);
