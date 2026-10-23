<?php

declare(strict_types=1);

use App\Models\Option;
use App\Models\Post;
use App\Models\Term;
use App\Post\Taxonomy;
use App\Post\Type;
use Expansa\Database\Model;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use Expansa\Security\Validator;

// run: php tests/Terms.php
require_once __DIR__ . '/bootstrap.php';
require_once EX_PATH . 'expansa/functions.php';

const EX_DB = ['prefix' => 'expansa_'];

// the result of a write: the number of the changed rows
final class Statement extends PDOStatement
{
    public function __construct(private readonly int $count = 1) {}

    public function rowCount(): int
    {
        return $this->count;
    }
}

// the database: rows by table, the conditions of Medoo the models use, the raw queries are kept to check them
$db = new class {
    public array $rows = [];
    public array $queries = [];
    private int $id = 0;

    public function select(string $table, array|string $columns, array $where = []): array
    {
        $rows = array_values(array_filter($this->rows[$table] ?? [], fn (array $row) => $this->matches($row, $where)));
        foreach (array_reverse($where['ORDER'] ?? [], true) as $column => $direction) {
            usort($rows, fn (array $a, array $b) => ($direction === 'DESC' ? -1 : 1) * ($a[$column] <=> $b[$column]));
        }

        $limit = $where['LIMIT'] ?? null;
        if ($limit !== null) {
            $rows = is_array($limit) ? array_slice($rows, $limit[0], $limit[1]) : array_slice($rows, 0, $limit);
        }

        if (is_string($columns) && $columns !== '*') {
            $name = explode(' ', $columns)[0];

            return array_column($rows, $name);
        }

        if (is_array($columns)) {
            $names = array_map(fn (string $column) => explode(' ', $column)[0], $columns);
            $rows  = array_map(fn (array $row) => array_intersect_key($row, array_flip($names)), $rows);
        }

        return $rows;
    }

    public function get(string $table, array|string $columns, array $where = []): mixed
    {
        return $this->select($table, $columns, [...$where, 'LIMIT' => 1])[0] ?? null;
    }

    public function has(string $table, array $where): bool
    {
        return $this->select($table, '*', $where) !== [];
    }

    public function count(string $table, array $where = []): int
    {
        return count($this->select($table, '*', $where));
    }

    public function insert(string $table, array $values): PDOStatement
    {
        foreach (array_is_list($values) ? $values : [$values] as $row) {
            $this->id             = max([$this->id, ...array_column($this->rows[$table] ?? [], 'id')]) + 1;
            $this->rows[$table][] = ['id' => $this->id, ...$row];
        }

        return new Statement();
    }

    public function id(): string
    {
        return (string) $this->id;
    }

    public function update(string $table, array $data, array $where): PDOStatement
    {
        foreach ($this->rows[$table] ?? [] as $index => $row) {
            if ($this->matches($row, $where)) {
                $this->rows[$table][$index] = [...$row, ...$data];
            }
        }

        return new Statement();
    }

    public function delete(string $table, array $where): PDOStatement
    {
        $rows               = $this->rows[$table] ?? [];
        $this->rows[$table] = array_values(array_filter($rows, fn (array $row) => ! $this->matches($row, $where)));

        return new Statement(count($rows) - count($this->rows[$table]));
    }

    public function query(string $statement, array $map = []): ?PDOStatement
    {
        $this->queries[] = $statement;

        return null;
    }

    public function hasTable(string $table): bool
    {
        return true;
    }

    public function schema(string $table): array
    {
        return ['deleted_at' => []];
    }

    private function matches(array $row, array $where): bool
    {
        foreach ($where as $key => $value) {
            if ($key === 'ORDER' || $key === 'LIMIT') {
                continue;
            }

            if ($key === 'OR') {
                if (! array_any(array_keys($value), fn ($inner) => $this->matches($row, [$inner => $value[$inner]]))) {
                    return false;
                }
                continue;
            }

            preg_match('/^([a-z_]+)(?:\[(!|~)\])?$/', $key, $parts);
            $actual = $row[$parts[1]] ?? null;
            $found  = match ($parts[2] ?? '') {
                '~'     => preg_match('/^' . str_replace('%', '.*', preg_quote((string) $value, '/')) . '$/', (string) $actual) === 1,
                default => is_array($value) ? in_array($actual, $value) : $actual == $value,
            };
            if ($found === (($parts[2] ?? '') === '!')) {
                return false;
            }
        }

        return true;
    }
};
Db::swap($db);

Model::configure(
    sanitizer: fn (array $data, array $rules) => Safe::data($data, $rules)->apply(),
    validator: fn (array $data, array $rules, bool $break) => new Validator($data, $rules, $break),
);

$options = new ReflectionProperty(Option::class, 'options');
$options->setValue(null, []);

Type::register(
    key: 'pages',
    labelName: 'Page',
    labelNamePlural: 'Pages',
    labelAllItems: 'Pages',
    labelAdd: 'Add',
    labelEdit: 'Edit',
    labelUpdate: 'Update',
    labelView: 'View',
    labelSearch: 'Search',
    labelSave: 'Save',
    hierarchical: true,
    showInMenu: false,
);
$taxonomy = fn (string $key, bool $hierarchical) => Taxonomy::register(
    key: $key,
    labelName: $key,
    labelNamePlural: $key,
    labelAdd: $key,
    labelEdit: $key,
    labelSearch: $key,
    types: ['pages'],
    hierarchical: $hierarchical,
    showInMenu: false,
);
$taxonomy('categories', true);
$taxonomy('tags', false);

// taxonomies
check('a taxonomy key of other characters is refused', throws(fn () => $taxonomy('Genres!', true), InvalidArgumentException::class));
check('a taxonomy registered twice keeps the first one', Taxonomy::register(key: 'tags', labelName: 'x', labelNamePlural: 'x', labelAdd: 'x', labelEdit: 'x', labelSearch: 'x')->types === ['pages']);
check('the taxonomies of a type', array_keys(Taxonomy::forType('pages')) === ['categories', 'tags'] && Taxonomy::forType('files') === []);
check('the taxonomies are filtered by their properties', array_keys(Taxonomy::filter(['hierarchical' => true])) === ['categories']);
check('the list of the terms is under the type', Taxonomy::get('tags')->url('pages') === 'edit?table=terms&taxonomy=tags&type=pages');

// the tree: news > world > europe, sport; a term of a missing parent is at the top
$db->rows['terms'] = [
    ['id' => 1, 'taxonomy' => 'categories', 'name' => 'News', 'slug' => 'news', 'parent_id' => 0, 'position' => 0, 'count' => 0],
    ['id' => 2, 'taxonomy' => 'categories', 'name' => 'World', 'slug' => 'world', 'parent_id' => 1, 'position' => 0, 'count' => 0],
    ['id' => 3, 'taxonomy' => 'categories', 'name' => 'Europe', 'slug' => 'europe', 'parent_id' => 2, 'position' => 0, 'count' => 0],
    ['id' => 4, 'taxonomy' => 'categories', 'name' => 'Sport', 'slug' => 'sport', 'parent_id' => 0, 'position' => 0, 'count' => 0],
    ['id' => 5, 'taxonomy' => 'categories', 'name' => 'Lost', 'slug' => 'lost', 'parent_id' => 99, 'position' => 0, 'count' => 0],
    ['id' => 6, 'taxonomy' => 'tags', 'name' => 'Hot', 'slug' => 'news', 'parent_id' => 0, 'position' => 0, 'count' => 0],
];
$db->rows['term_relations'] = [];

$tree = Term::tree('categories');
check('the tree lists a parent before its children, siblings by name', array_column($tree, 'id') === [5, 1, 2, 3, 4]);
check('the tree has the depth of each term', array_column($tree, 'depth', 'id') === [5 => 0, 1 => 0, 2 => 1, 3 => 2, 4 => 0]);

$paths = Term::paths('categories', [3, 4, 5]);
check('the path of a term is under the slugs of its parents', $paths[3] === 'news/world/europe' && $paths[4] === 'sport');
check('a missing parent is left out of the path', $paths[5] === 'lost');
check('a term of a flat taxonomy has its slug as the path', Term::paths('tags', [6]) === [6 => 'news']);
check('the URL of a term is by the structure of the terms', Term::find(3)->url() === url('categories/news/world/europe'));
check('a term is found by its path', Term::getByPath('categories', '/news/world/europe/')?->id === 3);
check('a term is not found by a path of other parents', Term::getByPath('categories', 'sport/europe') === null && Term::getByPath('categories', 'europe') === null);

check('a slug free in the taxonomy is kept', Term::uniqueSlug('categories', 'politics') === 'politics');
check('a slug of another taxonomy is free', Term::uniqueSlug('tags', 'sport') === 'sport');
check('a taken slug gets the first free number', Term::uniqueSlug('categories', 'news') === 'news-2');
$db->rows['terms'][] = ['id' => 7, 'taxonomy' => 'categories', 'name' => 'News 2', 'slug' => 'news-2', 'parent_id' => 0, 'position' => 0, 'count' => 0];
check('the numbers taken are skipped', Term::uniqueSlug('categories', 'news') === 'news-3');
check('a term keeps its own slug', Term::uniqueSlug('categories', 'news-2', 7) === 'news-2');

$europe = Term::find(3);
check('a parent of another taxonomy is refused', ! $europe->canHaveParent(6));
check('a descendant as the parent is refused: a loop', ! Term::find(1)->canHaveParent(3));
check('another branch as the parent is allowed', $europe->canHaveParent(4));
check('a term of a flat taxonomy has no parent', ! Term::find(6)->canHaveParent(1) && Term::find(6)->canHaveParent(0));

// the terms of a post
$db->rows['pages'] = [['id' => 10, 'title' => 'Hello', 'status' => 'publish', 'deleted_at' => null]];
$post = new Post(id: 10, title: 'Hello', status: 'publish', type: 'pages');

$db->queries = [];
check('the terms of a post are set', $post->setTerms('categories', [2, 4, 6]));
check('a term of another taxonomy is skipped', array_map(fn (Term $term) => $term->id, $post->terms('categories')) === [4, 2]);
check('the published posts of the terms are counted in a query per type', count($db->queries) === 2 && str_contains($db->queries[0], "r.term_id IN (2,4) AND p.status = 'publish' AND p.deleted_at IS NULL"));
check('the counts are saved in one update', $db->queries[1] === 'UPDATE <terms> SET <count> = CASE <id> WHEN 2 THEN 0 WHEN 4 THEN 0 END WHERE <id> IN (2,4)');

$post->setTerms('tags', [6]);
$post->setTerms('categories', [4]);
check('the removed terms go, the terms of another taxonomy stay', array_map(fn (Term $term) => $term->id, $post->terms()) === [6, 4]);
check('the terms of posts are read in two queries for all of them', array_keys(Term::getByPosts('pages', [10, 11])) === [10]);
check('a taxonomy not of the type is refused', ! new Post(id: 11, type: 'files')->setTerms('categories', [1]));

$options->setValue(null, ['taxonomies' => ['categories' => ['default' => 1]]]);
$post->setTerms('categories', []);
check('a post without terms gets the default one', array_map(fn (Term $term) => $term->id, $post->terms('categories')) === [1]);

check('the IDs of a taxonomy are taken, the others skipped', Term::ids('categories', ['3', '6', 'x', 0]) === [3]);
check('a name finds its term', Term::ids('tags', ['new:Hot']) === [6]);
check('a new name is not added without the right', Term::ids('tags', ['new:Cold']) === []);

$created = Term::ids('tags', ['new:Cold'], true);
$cold    = Term::find($created[0] ?? 0);
check('a new name is added with the right', $cold instanceof Term && $cold->taxonomy === 'tags' && $cold->slug === 'cold');

// deleting
check('the default term is not deleted', ! Term::find(1)->delete());

$post->setTerms('categories', [2]);
check('a term is deleted', Term::find(2)->delete() && Term::find(2) instanceof Expansa\Support\Error);
check('its children move up to its parent', Term::find(3)->parentId === 1);
check('a post left without terms gets the default one', array_map(fn (Term $term) => $term->id, $post->terms('categories')) === [1]);

exit($failures > 0 ? 1 : 0);
