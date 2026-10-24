<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Taxonomy;
use App\Post\Type;
use App\Support\Activity;
use App\Support\Permalinks;
use DateTime;
use Expansa\Database\Contracts\Fieldable;
use Expansa\Database\FieldEav;
use Expansa\Database\Model;
use Expansa\Database\Query;
use Expansa\Database\Traits\HasFieldEav;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasTimestamps;
use Expansa\Database\Traits\HasValidation;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;
use Expansa\Support\Error;
use PDO;

/**
 * Term of a taxonomy, see App\Post\Taxonomy: a category, a tag. The terms of every taxonomy are in one table,
 * the slug is unique within the taxonomy, a term of a hierarchical one may have a parent of the same taxonomy.
 *
 * A post gets its terms with Post::setTerms(), the links are rows of `term_relations` addressed as in `slugs`:
 * by the table of the post and its ID. `count` is the number of the published posts out of the trash,
 * recount() keeps it. The default term of a taxonomy, the `taxonomies.<key>.default` option, takes the posts
 * left without terms when a term is deleted and can not be deleted itself.
 *
 * Changes run the `termsChanged` hook with the key of the taxonomy.
 *
 * @property int           $id
 * @property string        $taxonomy    Key of the taxonomy.
 * @property string        $name
 * @property string        $slug
 * @property string        $description
 * @property int           $parentId    ID of the parent term, 0 for a top-level one.
 * @property int           $position    Order among the terms of the same parent.
 * @property int           $count       Number of the published posts with the term.
 * @property DateTime|null $createdAt
 * @property DateTime|null $updatedAt
 * @property FieldEav      $field       Custom fields of the term.
 *
 * @package App\Models
 */
class Term extends Model implements Fieldable
{
    use HasFieldEav;
    use HasSanitizing;
    use HasTimestamps;
    use HasValidation;

    public protected(set) string $table = 'terms';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    public protected(set) array $fillable = [
        'taxonomy',
        'name',
        'slug',
        'description',
        'parent_id',
        'position',
    ];

    protected function getSanitizerRules(): array
    {
        return [
            'taxonomy'    => 'trim',
            'name'        => 'text',
            'slug'        => 'slug:$name',
            'description' => 'textarea',
            'parent_id'   => 'absint',
            'position'    => 'absint',
        ];
    }

    protected function validatorRules(): array
    {
        return [
            'taxonomy' => 'required',
            'name'     => 'required',
            'slug'     => 'required|slug',
        ];
    }

    protected function validatorExtend(): void
    {
    }

    /**
     * Find a term by ID.
     *
     * @param int $id
     * @return Term|Error
     */
    public static function find(int $id): Term|Error
    {
        $term = $id > 0 ? parent::get($id) : null;

        return $term instanceof Term ? $term : error('term-find', t('Term not found'));
    }

    /**
     * Find a term by its slug in a taxonomy.
     *
     * @param string $taxonomy
     * @param string $slug
     * @return Term|null
     */
    public static function findBySlug(string $taxonomy, string $slug): ?Term
    {
        $row = $slug !== '' ? Db::get('terms', '*', ['taxonomy' => $taxonomy, 'slug' => $slug]) : null;

        return is_array($row) ? self::hydrate($row) : null;
    }

    /**
     * Create a term: the slug is made from the name when empty and gets a suffix when taken in the taxonomy,
     * a parent is kept only in a hierarchical taxonomy and from the same one.
     *
     * @param array<string, mixed> $data `taxonomy`, `name`, `slug`, `description`, `parent_id`, `position`.
     * @return Term|Error
     */
    public static function create(array $data): Term|Error
    {
        $taxonomy = Taxonomy::get((string) ($data['taxonomy'] ?? ''));
        if (! $taxonomy instanceof Taxonomy) {
            return error('term-add', t('Taxonomy is not registered.'));
        }

        // the name goes before the slug: an empty slug is made from it
        $term = new self([
            'taxonomy'    => $taxonomy->key,
            'name'        => $data['name'] ?? '',
            'slug'        => $data['slug'] ?? '',
            'description' => $data['description'] ?? '',
            'parent_id'   => $data['parent_id'] ?? 0,
            'position'    => $data['position'] ?? 0,
        ]);
        $term->slug = self::uniqueSlug($taxonomy->key, $term->slug);

        if (! $term->canHaveParent($term->parentId)) {
            $term->parentId = 0;
        }

        if (! $term->isValid()) {
            return error('term-add', $term->getValidatorErrors());
        }

        if (! $term->save() instanceof self) {
            return error('term-add', t('Failed to save the term to the database.'));
        }

        Hook::run('termsChanged', $term->taxonomy);

        return $term;
    }

    /**
     * Update the term with the given attributes; the taxonomy stays, a slug taken by another term
     * gets a suffix, a parent that would make a loop keeps the saved one.
     *
     * @param array<string, mixed> $data `name`, `slug`, `description`, `parent_id`, `position`.
     * @return Term|Error
     */
    public function update(array $data): Term|Error
    {
        $before = $this->attributes;
        $data   = array_intersect_key($data, array_flip(['name', 'slug', 'description', 'parent_id', 'position']));

        // an emptied slug is made from the name, the new one if it changes too
        if (array_key_exists('slug', $data) && trim((string) $data['slug']) === '') {
            $data['slug'] = (string) ($data['name'] ?? $this->name);
        }

        $this->fill($data);
        if (array_key_exists('slug', $data)) {
            $this->slug = self::uniqueSlug($this->taxonomy, $this->slug, $this->id);
        }

        if (array_key_exists('parent_id', $data) && ! $this->canHaveParent($this->parentId)) {
            $this->parentId = (int) $before['parent_id'];
        }

        if (! $this->isValid()) {
            return error('term-update', $this->getValidatorErrors());
        }

        if (! $this->save() instanceof self) {
            return error('term-update', t('Failed to save the term to the database.'));
        }

        Activity::changes($this->table, $this->id, $before, array_intersect_key($this->attributes, $data));
        Hook::run('termsChanged', $this->taxonomy);

        return $this;
    }

    /**
     * Delete the term permanently with its fields and links: its children move up to its parent,
     * the posts left without terms of the taxonomy get the default term.
     *
     * @return bool False for the default term or when there was no such row.
     */
    public function delete(): bool
    {
        $default = self::defaultId($this->taxonomy);
        if ($this->id === $default) {
            return false;
        }

        $posts = Db::select('term_relations', ['entity_table', 'entity_id [Int]'], ['term_id' => $this->id]) ?: [];
        if (new Query($this)->delete() === 0) {
            return false;
        }

        Db::delete('term_relations', ['term_id' => $this->id]);
        Db::update('terms', ['parent_id' => $this->parentId], ['taxonomy' => $this->taxonomy, 'parent_id' => $this->id]);
        Activity::forget($this->table, $this->id);

        if ($default > 0 && $posts !== []) {
            $ids = [];
            foreach ($posts as $post) {
                $ids[$post['entity_table']][] = $post['entity_id'];
            }

            foreach ($ids as $table => $list) {
                $taken = array_column(self::relations($table, $list, $this->taxonomy), 'entity_id');
                $rows  = array_map(fn (int $id) => [
                    'term_id'      => $default,
                    'entity_table' => $table,
                    'entity_id'    => $id,
                ], array_values(array_diff($list, $taken)));
                if ($rows !== []) {
                    Db::insert('term_relations', $rows);
                }
            }
            self::recount([$default]);
        }

        Hook::run('termsChanged', $this->taxonomy);

        return true;
    }

    /**
     * Whether a term can be the parent of this one: the taxonomy is hierarchical, the parent is a term
     * of the same taxonomy and neither this term nor one of its descendants, so no loop appears.
     *
     * @param int $parentId `0` for no parent, always allowed.
     * @return bool
     */
    public function canHaveParent(int $parentId): bool
    {
        if ($parentId === 0) {
            return true;
        }

        if (! (Taxonomy::get($this->taxonomy)->hierarchical ?? false)) {
            return false;
        }

        // up from the parent to the root: meeting this term means the parent is its descendant
        $seen = [];
        for ($id = $parentId; $id > 0; $id = (int) $next) {
            if ($id === ($this->id ?? 0) || isset($seen[$id])) {
                return false;
            }

            $seen[$id] = true;
            $next      = Db::get('terms', 'parent_id', ['id' => $id, 'taxonomy' => $this->taxonomy]);
            if (! is_scalar($next)) {
                return $id !== $parentId;
            }
        }

        return true;
    }

    /**
     * Get the URL of the term by the permalink structure, see Permalinks::term().
     *
     * @param string|null $path Path of the term by paths(), read when null.
     * @return string Empty for a term of a taxonomy without pages.
     */
    public function url(?string $path = null): string
    {
        if (! (Taxonomy::get($this->taxonomy)->public ?? false)) {
            return '';
        }

        $path = Permalinks::term($path ?? (self::paths($this->taxonomy, [$this->id])[$this->id] ?? ''), $this->taxonomy);

        return $path === '' ? '' : url($path);
    }

    /**
     * Get a term by the path of its URL, see paths(): `parent/child` in a hierarchical taxonomy.
     *
     * @param string $taxonomy
     * @param string $path
     * @return Term|null Null when there is no such term or the path is not its own.
     */
    public static function getByPath(string $taxonomy, string $path): ?Term
    {
        $path = trim($path, '/');
        $term = self::findBySlug($taxonomy, substr($path, (int) strrpos('/' . $path, '/')));

        // a term of a hierarchical taxonomy opens only under the slugs of its parents
        return $term !== null && (self::paths($taxonomy, [$term->id])[$term->id] ?? '') === $path ? $term : null;
    }

    /**
     * Get the paths of terms in their URLs: the slug, under the slugs of the parents in a hierarchical
     * taxonomy, `parent/child`. The parents are read a level per query.
     *
     * @param string $taxonomy
     * @param int[]  $ids
     * @return array<int, string> Paths by ID.
     */
    public static function paths(string $taxonomy, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(intval(...), $ids))));
        if ($ids === []) {
            return [];
        }

        $hierarchical = Taxonomy::get($taxonomy)->hierarchical ?? false;

        $terms = [];
        $level = $ids;
        while ($level !== []) {
            $next = [];
            $rows = Db::select('terms', ['id [Int]', 'parent_id [Int]', 'slug'], ['id' => $level, 'taxonomy' => $taxonomy]) ?: [];
            foreach ($rows as $row) {
                $terms[$row['id']] = $row;
                if ($hierarchical && $row['parent_id'] > 0 && ! isset($terms[$row['parent_id']])) {
                    $next[$row['parent_id']] = $row['parent_id'];
                }
            }
            $level = array_values($next);
        }

        $paths = [];
        foreach ($ids as $id) {
            if (! isset($terms[$id])) {
                continue;
            }

            $path   = [$terms[$id]['slug']];
            $seen   = [$id => true];
            $parent = $hierarchical ? $terms[$id]['parent_id'] : 0;
            while (isset($terms[$parent]) && ! isset($seen[$parent])) {
                $seen[$parent] = true;
                array_unshift($path, $terms[$parent]['slug']);
                $parent = $terms[$parent]['parent_id'];
            }

            $paths[$id] = implode('/', $path);
        }

        return $paths;
    }

    /**
     * Get the terms of a taxonomy in the order of a tree: a parent before its children, siblings
     * by position and name; a term whose parent is gone is shown at the top.
     *
     * @param string $taxonomy
     * @return array<int, array{id: int, name: string, slug: string, parent_id: int, count: int, depth: int}>
     */
    public static function tree(string $taxonomy): array
    {
        $rows = Db::select('terms', ['id [Int]', 'name', 'slug', 'parent_id [Int]', 'count [Int]'], [
            'taxonomy' => $taxonomy,
            'ORDER'    => ['position' => 'ASC', 'name' => 'ASC'],
        ]) ?: [];

        $ids      = array_flip(array_column($rows, 'id'));
        $children = [];
        foreach ($rows as $row) {
            $children[isset($ids[$row['parent_id']]) && $row['parent_id'] !== $row['id'] ? $row['parent_id'] : 0][] = $row;
        }

        $tree = [];
        $walk = function (int $parentId, int $depth) use (&$walk, &$tree, $children): void {
            foreach ($children[$parentId] ?? [] as $row) {
                // a loop of parents written past canHaveParent() is cut
                if (isset($tree[$row['id']])) {
                    continue;
                }

                $tree[$row['id']] = [...$row, 'depth' => $depth];
                $walk($row['id'], $depth + 1);
            }
        };
        $walk(0, 0);

        return array_values($tree);
    }

    /**
     * Get the terms of posts, two queries for all of them: the links, then the terms, by name.
     *
     * @param string      $table    Table of the posts, see Post::$table.
     * @param int[]       $ids      IDs of the posts.
     * @param string|null $taxonomy Key of a taxonomy, null for the terms of every one.
     * @return array<int, Term[]> Terms by post ID; a post without terms has none.
     */
    public static function getByPosts(string $table, array $ids, ?string $taxonomy = null): array
    {
        $relations = self::relations($table, $ids, $taxonomy);
        if ($relations === []) {
            return [];
        }

        $where = ['id' => array_values(array_unique(array_column($relations, 'term_id'))), 'ORDER' => ['name' => 'ASC']];
        $terms = [];
        foreach (Db::select('terms', '*', $where) ?: [] as $row) {
            $terms[(int) $row['id']] = self::hydrate($row);
        }

        $posts = [];
        foreach ($terms as $termId => $term) {
            foreach ($relations as $relation) {
                if ($relation['term_id'] === $termId) {
                    $posts[$relation['entity_id']][] = $term;
                }
            }
        }

        return $posts;
    }

    /**
     * Get the links of posts to the terms, of a taxonomy or of every one.
     *
     * @param string      $table
     * @param int[]       $ids
     * @param string|null $taxonomy
     * @return array<int, array{term_id: int, entity_id: int}>
     */
    public static function relations(string $table, array $ids, ?string $taxonomy = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map(intval(...), $ids))));
        if ($ids === []) {
            return [];
        }

        $where = ['entity_table' => $table, 'entity_id' => $ids];
        if ($taxonomy !== null) {
            $terms = Db::select('terms', 'id [Int]', ['taxonomy' => $taxonomy]) ?: [];
            if ($terms === []) {
                return [];
            }
            $where['term_id'] = $terms;
        }

        return Db::select('term_relations', ['term_id [Int]', 'entity_id [Int]'], $where) ?: [];
    }

    /**
     * Count the published posts out of the trash of the terms, a query per post type of their taxonomies
     * and one update for all of them.
     *
     * @param int[] $ids IDs of the terms.
     * @return void
     */
    public static function recount(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map(intval(...), $ids))));
        if ($ids === []) {
            return;
        }

        $taxonomies = array_unique(Db::select('terms', 'taxonomy', ['id' => $ids]) ?: []);

        $types = [];
        foreach ($taxonomies as $key) {
            foreach (Taxonomy::get($key)->types ?? [] as $type) {
                $types[$type] = Type::get($type);
            }
        }

        $counts = array_fill_keys($ids, 0);
        $list   = implode(',', $ids);
        foreach (array_filter($types) as $type) {
            $trash = $type->canTrash ? ' AND p.deleted_at IS NULL' : '';
            $rows  = Db::query(
                "SELECT r.term_id, COUNT(*) FROM <term_relations> r JOIN <$type->table> p ON p.id = r.entity_id"
                . " WHERE r.entity_table = :table AND r.term_id IN ($list) AND p.status = 'publish'$trash GROUP BY r.term_id",
                [':table' => $type->table]
            )?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

            foreach ($rows as $termId => $count) {
                $counts[(int) $termId] += (int) $count;
            }
        }

        $cases = '';
        foreach ($counts as $termId => $count) {
            $cases .= " WHEN $termId THEN $count";
        }
        Db::query("UPDATE <terms> SET <count> = CASE <id>$cases END WHERE <id> IN ($list)");

        // the rows of Model::get() are cached by ID
        foreach ($ids as $id) {
            Cache::forget((string) $id, 'terms');
        }
    }

    /**
     * Count the terms of posts again, e.g. after their status or the trash changed.
     *
     * @param string $table Table of the posts.
     * @param int[]  $ids   IDs of the posts.
     * @return void
     */
    public static function recountPosts(string $table, array $ids): void
    {
        self::recount(array_column(self::relations($table, $ids), 'term_id'));
    }

    /**
     * Get the ID of the default term of a taxonomy, the `taxonomies.<key>.default` option.
     *
     * @param string $taxonomy
     * @return int 0 without one.
     */
    public static function defaultId(string $taxonomy): int
    {
        return (int) Option::get("taxonomies.$taxonomy.default", 0);
    }

    /**
     * Get a slug free in the taxonomy: the given one or with the first free number, `news-2`;
     * one query for every slug with a number.
     *
     * @param string $taxonomy
     * @param string $slug
     * @param int    $except ID of the term that keeps its own slug.
     * @return string
     */
    public static function uniqueSlug(string $taxonomy, string $slug, int $except = 0): string
    {
        if ($slug === '') {
            return '';
        }

        $where = [
            'taxonomy' => $taxonomy,
            'OR'       => [
                'slug'    => $slug,
                'slug[~]' => $slug . '-%',
            ],
        ];
        if ($except > 0) {
            $where['id[!]'] = $except;
        }

        $taken = array_flip(Db::select('terms', 'slug', $where) ?: []);
        if (! isset($taken[$slug])) {
            return $slug;
        }

        $suffix = 2;
        while (isset($taken["$slug-$suffix"])) {
            $suffix++;
        }

        return "$slug-$suffix";
    }

    /**
     * Get the terms of a taxonomy by the IDs or the names: an ID of another taxonomy is skipped, a name
     * finds the term of the same name or, when $create, makes a new one.
     *
     * @param string                 $taxonomy
     * @param array<int, int|string> $values   IDs, and names as `new:Name`.
     * @param bool                   $create   Whether a name without a term makes one.
     * @return int[] IDs of the terms.
     */
    public static function ids(string $taxonomy, array $values, bool $create = false): array
    {
        $ids   = [];
        $names = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if (str_starts_with($value, 'new:')) {
                $names[] = trim(substr($value, 4));
            } elseif (ctype_digit($value) && $value !== '0') {
                $ids[] = (int) $value;
            }
        }

        $ids = $ids === [] ? [] : (Db::select('terms', 'id [Int]', ['id' => $ids, 'taxonomy' => $taxonomy]) ?: []);
        foreach (array_unique(array_filter($names)) as $name) {
            $found = Db::get('terms', 'id [Int]', ['taxonomy' => $taxonomy, 'name' => $name]);
            if (is_int($found)) {
                $ids[] = $found;
            } elseif ($create) {
                $term = self::create(['taxonomy' => $taxonomy, 'name' => $name]);
                if ($term instanceof self) {
                    $ids[] = $term->id;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
