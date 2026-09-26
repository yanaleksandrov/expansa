<?php

declare(strict_types=1);

use Expansa\Database\Query\Builder;

// run: php tests/Database.php
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

// test mode builds the SQL without connecting or running it
$db  = new Builder(['driver' => 'mysql', 'type' => 'mysql', 'prefix' => 'x_', 'testMode' => true]);
$sql = function (string $method, mixed ...$args) use ($db): string {
    $db->$method(...$args);

    return $db->queryString;
};

check('select with conditions and a limit', $sql('select', 'posts', ['id', 'title'], ['status' => 'publish', 'id[>]' => 5, 'LIMIT' => 10])
    === 'SELECT "id","title" FROM "x_posts" WHERE "status" = \'publish\' AND "id" > 5 LIMIT 10');
check('insert', $sql('insert', 'posts', ['title' => 'a']) === 'INSERT INTO "x_posts" ("title") VALUES (\'a\')');
check('update', $sql('update', 'posts', ['title' => 'x'], ['id' => 3]) === 'UPDATE "x_posts" SET "title" = \'x\' WHERE "id" = 3');
check('delete by a list of ids', $sql('delete', 'posts', ['id' => [1, 2]]) === 'DELETE FROM "x_posts" WHERE "id" IN (1, 2)');
check('like', $sql('select', 'posts', '*', ['title[~]' => '%x']) === 'SELECT * FROM "x_posts" WHERE ("title" LIKE \'%x\')');

// values and identifiers can't break out of the query
check('a quote in a value is escaped', $sql('select', 'posts', '*', ['title' => "x' OR '1'='1"])
    === 'SELECT * FROM "x_posts" WHERE "title" = \'x\'\' OR \'\'1\'\'=\'\'1\'');
check('an injected column name is reduced to the identifier', $sql('select', 'posts', '*', ['id" OR 1=1 --' => 1])
    === 'SELECT * FROM "x_posts" WHERE "id" = 1');

exit($failures > 0 ? 1 : 0);
