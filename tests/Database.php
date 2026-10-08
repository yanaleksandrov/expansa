<?php

declare(strict_types=1);

use Expansa\Database\Attribute;
use Expansa\Database\Exceptions\InvalidConnection;
use Expansa\Database\Model;
use Expansa\Database\Query\Builder;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasSoftDeletes;
use Expansa\Database\Traits\HasUuid;
use Expansa\Facades\Db;
use Expansa\Support\Str;

// run: php tests/Database.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

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
check('has() selects the constant 1', $sql('has', 'posts', ['id' => 1]) === 'SELECT EXISTS(SELECT 1 FROM "x_posts" WHERE "id" = 1)');
check('a raw foreign key prefixes both tables', $sql('query', 'ALTER TABLE <posts_fields> ADD CONSTRAINT `fk` FOREIGN KEY (`post_id`) REFERENCES <posts> (`id`)')
    === 'ALTER TABLE "x_posts_fields" ADD CONSTRAINT `fk` FOREIGN KEY (`post_id`) REFERENCES "x_posts" (`id`)');

// values and identifiers can't break out of the query
check('a quote in a value is escaped', $sql('select', 'posts', '*', ['title' => "x' OR '1'='1"])
    === 'SELECT * FROM "x_posts" WHERE "title" = \'x\'\' OR \'\'1\'\'=\'\'1\'');
check('an injected column name is reduced to the identifier', $sql('select', 'posts', '*', ['id" OR 1=1 --' => 1])
    === 'SELECT * FROM "x_posts" WHERE "id" = 1');

check('bad connection options throw InvalidConnection', throws(fn () => new Builder(['driver' => 'nope', 'host' => 'x', 'database' => 'x']), InvalidConnection::class));

// models: the services come from configure(), here plain closures instead of the Cache and Security packages
final class Post extends Model
{
    use HasSanitizing;
    use HasSoftDeletes;

    public protected(set) string $table = 'posts';

    public protected(set) array $fillable = ['title', 'password'];

    protected function getSanitizerRules(): array
    {
        return ['title' => 'trim'];
    }

    protected function password(): Attribute
    {
        return new Attribute(set: fn (string $value) => strrev($value), get: fn (?string $value) => "*$value");
    }
}

final class Tag extends Model
{
    public protected(set) string $table = 'tags';
}

final class Member extends Model
{
    use HasUuid;

    public protected(set) string $table = 'members';

    public protected(set) array $fillable = ['name', 'uuid'];
}

Db::configure(driver: 'mysql', database: 'x', username: '', password: '', host: '', testMode: true);

check('a sanitizer rule without a sanitizer throws', throws(fn () => new Post(['title' => ' x ']), LogicException::class));

$cache = [];
Model::configure(
    cache: function (string $key, string $group, ?Closure $callback = null) use (&$cache) {
        return array_key_exists("$group:$key", $cache) ? $cache["$group:$key"] : ($cache["$group:$key"] = $callback ? $callback() : null);
    },
    forgetCache: function (string $key, string $group) use (&$cache) {
        unset($cache["$group:$key"]);
    },
    sanitizer: fn (array $data, array $rules) => array_map(trim(...), array_intersect_key($data, $rules)),
);

$post = new Post(['title' => '  Hello  ', 'password' => 'abc', 'id' => 5]);
check('fill() sanitizes and applies mutators', $post->attributes === ['title' => 'Hello', 'password' => 'cba']);
check('a read mutator applies', $post->password === '*cba');
check('fill() skips unfillable keys', $post->id === null);
check('a model without fillable attributes rejects fill()', throws(fn () => new Tag(['name' => 'x']), LogicException::class));

$row = Post::hydrate(['id' => 7, 'title' => ' raw ', 'deleted_at' => null]);
check('hydrate() keeps the row as is', $row->title === ' raw ' && $row->getChanges() === []);
$row->title = 'New';
check('changes are tracked', $row->getChanges() === ['title' => 'New']);
check('the soft delete column', $row->deletedAtColumn === 'deleted_at');

Post::get(9);
check('get() by id goes through the cache', array_key_exists('posts:9', $cache));
check('the soft delete scope', Db::instance()->queryString === 'SELECT * FROM "posts" WHERE "id" = 9 AND "deleted_at" IS NULL LIMIT 1');
check('an unknown method throws', throws(fn () => Post::nope(), BadMethodCallException::class));

// UUIDs come from PHP, not from a trigger the host may refuse
$uuids = [Str::uuid7(), Str::uuid7()];
check('uuid7() is a version 7 UUID with the RFC variant', array_all($uuids, fn (string $uuid) => (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) && $uuids[0] !== $uuids[1]);
check('uuid7() starts with the time in milliseconds', hexdec(str_replace('-', '', substr($uuids[0], 0, 13))) - (int) (microtime(true) * 1000) <= 0);

new Member(['name' => 'a'])->save();
check('save() of a new model with HasUuid fills the UUID', (bool) preg_match('/^INSERT INTO "members" \("name", "uuid"\) VALUES \(\'a\', \'[0-9a-f-]{36}\'\)$/', Db::instance()->queryString));

new Member(['name' => 'b', 'uuid' => 'kept'])->save();
check('save() keeps a UUID that is set', str_contains(Db::instance()->queryString, "'kept'"));

exit($failures > 0 ? 1 : 0);
