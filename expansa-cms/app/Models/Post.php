<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Status;
use App\Post\Type;
use App\Support\Activity;
use App\Support\Permalinks;
use Expansa\Database\FieldEav;
use Expansa\Database\Model;
use Expansa\Database\Traits\HasSoftDeletes;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use Expansa\Support\Str;
use LogicException;

/**
 * Class representing an entry entity.
 *
 * Deleting goes through the trash like in other models with HasSoftDeletes: delete() stamps `deleted_at`,
 * restore() clears it. A type registered with `canTrash: false` turns soft deletes off, so delete()
 * removes its entries at once.
 */
class Post
{
    use HasSoftDeletes;

    /**
     * Permission a user needs to be the author of an entry.
     */
    public const string AUTHOR_PERMISSION = 'types_edit';

    public function __construct(

        /**
         * Entry id, 0 for an entry not saved yet.
         */
        public int $id = 0,

        /**
         * Entry title.
         */
        public string $title = '',

        /**
         * Entry content.
         */
        public string $content = '',

        /**
         * Author user id, 0 when there is no author.
         */
        public int $authorId = 0,

        /**
         * Parent entry id, 0 for a top-level entry.
         */
        public int $parentId = 0,

        /**
         * Number of comments.
         */
        public int $comments = 0,

        /**
         * Number of views.
         */
        public int $views = 0,

        /**
         * Sort position.
         */
        public int $position = 0,

        /**
         * Visibility status: publish, pending, draft, protected, private or future.
         */
        public string $status = 'pending',

        /**
         * Discussion status: open or closed.
         */
        public string $discussion = 'open',

        /**
         * Password protecting the entry, empty for none.
         */
        public string $password = '',

        /**
         * Creation date and time.
         */
        public string $createdAt = '',

        /**
         * Last update date and time.
         */
        public string $updatedAt = '',

        /**
         * Post type key, see App\Post\Type.
         */
        public string $type = '',

        /**
         * Database table name; always overwritten with the name derived from $type.
         */
        public string $table = '',

        /**
         * Unique string identifier of the entry.
         */
        public string $uuid = '',

        /**
         * Full URL of the entry.
         */
        public string $link = '',

        /**
         * Unique URL slug, empty for entries of non-public types.
         */
        public string $slug = '',

        /**
         * Custom fields of the entry.
         */
        public ?Field $field = null,

        /**
         * Date and time the entry was moved to the trash, null outside the trash.
         */
        public ?string $deletedAt = null,
    ) {
        $this->table      = Safe::tablename($this->type);
        $this->softDelete = Type::get($this->type)?->canTrash ?? false;
    }

    /**
     * Add new post.
     *
     * @param string $type
     * @param array $args
     *
     * @return ?Post
     */
    public static function add(string $type, array $args): ?Post
    {
        $user = User::current();
        $type = Type::get($type);
        if (! $type instanceof Type) {
            throw new LogicException(t('Post type is not registered.'));
        }

        $data = Safe::data(
            $args,
            [
                'title'      => 'text',
                'content'    => 'markup',
                'author_id'  => 'absint:' . ($user->id ?? '0'),
                'parent_id'  => 'absint:0',
                'comments'   => 'absint:0',
                'views'      => 'absint:0',
                'position'   => 'absint:0',
                'status'     => 'text:draft',
                'discussion' => 'text:' . (Option::get('comments.default_status', true) ? 'open' : 'closed'),
                'password'   => 'text',
            ]
        )->apply();

        // a parent only for a hierarchical type, and one that exists
        if ($data['parent_id'] > 0 && (! $type->hierarchical || ! Db::has($type->table, ['id' => $data['parent_id']]))) {
            $data['parent_id'] = 0;
        }

        // insert to DB
        Db::insert($type->table, ['uuid' => Str::uuid7()] + array_diff_key($data, array_flip([ 'slug', 'fields' ])));

        $post = self::get($type->key, Db::id());
        if ($post instanceof Post) {
            $post->type  = $type->key;
            $post->table = $type->table;

            /**
             * Add slug just if post type is public.
             */
            if ($type->public === true) {
                $slug = Slug::create([
                    'entity_id'    => $post->id,
                    'entity_table' => $type->table,
                    'slug'         => Safe::slug(($args['slug'] ?? '') ?: $data['title']),
                ]);

                if ($slug instanceof Slug) {
                    $post->slug = $slug->slug;
                    $post->link = '';
                }
            }

            $fields = Safe::array($args['fields'] ?? []);
            if ($fields) {
                ( new FieldEav($post) )->import($fields);
            }
        }

        return $post;
    }

    /**
     * Get a post by the path of its URL, see path(): `parent/child` for a hierarchical type.
     *
     * @param string $path
     * @return Post|null Null when there is no such post, it is in the trash or the path is not its own.
     */
    public static function getByPath(string $path): ?Post
    {
        $path = trim($path, '/');
        $slug = Slug::find(substr($path, (int) strrpos('/' . $path, '/')), 'slug');
        $post = $slug instanceof Slug ? self::get(Safe::tablename($slug->entityTable), $slug->entityId) : null;

        // a page of a hierarchical type opens only under the slugs of its parents
        return $post === null || $post->isTrashed() || $post->path() !== $path ? null : $post;
    }

    /**
     * Get the path of the post in its URL: the slug, under the slugs of the parents for a hierarchical type.
     *
     * @return string `parent/child`, empty for a post without a slug.
     */
    public function path(): string
    {
        return self::paths($this->type, [$this->id])[$this->id] ?? '';
    }

    /**
     * Get the URL of the post by the permalink structure, see Permalinks::post().
     *
     * @param string|null $path Path of the post by paths(), read when null.
     * @return string Empty for a post without a slug.
     */
    public function url(?string $path = null): string
    {
        $path = Permalinks::post($path ?? $this->path(), $this->id, $this->type);

        return $path === '' ? '' : url($path);
    }

    /**
     * Get the paths of posts of a public type in their URLs: the slug, under the slugs of the parents
     * for a hierarchical type, `parent/child`. The parents are read a level per query, the slugs in one.
     *
     * @param string $type Key of the post type.
     * @param int[]  $ids
     * @return array<int, string> Paths by ID; a post without a slug has none, a parent without one is skipped.
     */
    public static function paths(string $type, array $ids): array
    {
        $type = Type::get($type);
        $ids  = array_values(array_unique(array_filter(array_map(intval(...), $ids))));
        if (! $type instanceof Type || ! $type->public || $ids === []) {
            return [];
        }

        $parents = [];
        $level   = $type->hierarchical ? $ids : [];
        while ($level !== []) {
            $next = [];
            foreach (Db::select($type->table, ['id [Int]', 'parent_id [Int]'], ['id' => $level]) ?: [] as $row) {
                $parents[$row['id']] = $row['parent_id'];
                if ($row['parent_id'] > 0 && ! isset($parents[$row['parent_id']])) {
                    $next[$row['parent_id']] = $row['parent_id'];
                }
            }
            $level = array_values($next);
        }

        $slugs = array_column(Db::select('slugs', ['entity_id [Int]', 'slug'], [
            'entity_table' => $type->table,
            'entity_id'    => array_values(array_unique([...$ids, ...array_keys($parents)])),
        ]) ?: [], 'slug', 'entity_id');

        $paths = [];
        foreach ($ids as $id) {
            if (! isset($slugs[$id])) {
                continue;
            }

            $path = [$slugs[$id]];
            $seen = [$id => true];
            for ($parent = $parents[$id] ?? 0; $parent > 0 && ! isset($seen[$parent]); $parent = $parents[$parent] ?? 0) {
                $seen[$parent] = true;
                if (isset($slugs[$parent])) {
                    array_unshift($path, $slugs[$parent]);
                }
            }

            $paths[$id] = implode('/', $path);
        }

        return $paths;
    }

    /**
     * Get post by field.
     *
     * @param string     $type
     * @param int|string $value
     * @param string     $field
     * @return null|Post
     */
    public static function get(string $type, int|string $value, string $field = 'id'): ?Post
    {
        $type = Type::get($type);
        if (! $type instanceof Type) {
            return null;
        }

        $data = Db::get($type->table, '*', [ $field => $value ]);

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[ $key ]);
                $data[ Safe::camelcase($key) ] = $value;
            }

            $slug         = $type->public === true ? Slug::findByEntity((int) $data['id'], $type->table) : null;
            $data['slug'] = $slug instanceof Slug ? $slug->slug : '';
            $data['type'] = $type->key;

            return new Post(...$data);
        }

        return null;
    }

    /**
     * Update the given columns of the post and, for a public type, its slug; other columns keep their values.
     *
     * @param array<string, mixed> $args `title`, `content`, `parent_id`, `author_id`, `status`, `discussion`, `password`, `slug`.
     * @return bool False when nothing was saved, e.g. the slug is taken.
     */
    public function update(array $args): bool
    {
        $type = Type::get($this->type);
        if (! $type instanceof Type || $this->id === 0) {
            return false;
        }

        $rules = [
            'title'      => 'text',
            'content'    => 'markup',
            'parent_id'  => 'absint:0',
            'author_id'  => 'absint:0',
            'status'     => 'text:draft',
            'discussion' => 'text:open',
            'password'   => 'text',
        ];

        $data = Safe::data(array_intersect_key($args, $rules), array_intersect_key($rules, $args))->apply();

        // an author who is not a user any more or may not edit entries keeps the saved one
        if (isset($data['author_id'])) {
            $author = $data['author_id'] > 0 ? User::find($data['author_id']) : null;
            if (! $author instanceof User || ! $author->can(self::AUTHOR_PERMISSION)) {
                unset($data['author_id']);
            }
        }

        // a parent that would make a loop or is not allowed for the type keeps the saved one
        if (isset($data['parent_id']) && ! $this->canHaveParent($data['parent_id'])) {
            unset($data['parent_id']);
        }

        if (isset($data['status']) && ! array_key_exists($data['status'], Status::getStatuses())) {
            unset($data['status']);
        }

        if (isset($data['discussion']) && ! in_array($data['discussion'], ['open', 'closed'], true)) {
            unset($data['discussion']);
        }

        $before = ['slug' => $this->slug];
        foreach ($data as $key => $value) {
            $before[$key] = $this->{Safe::camelcase($key)};
        }

        if ($type->public === true && isset($args['slug'])) {
            $value = Safe::slug((string) $args['slug'] ?: ($data['title'] ?? $this->title));
            $slug  = Slug::findByEntity($this->id, $type->table);
            $slug  = $slug instanceof Slug
                ? $slug->update(['slug' => $value])
                : Slug::create(['entity_id' => $this->id, 'entity_table' => $type->table, 'slug' => $value]);

            if (! $slug instanceof Slug) {
                return false;
            }
            $this->slug = $slug->slug;
        }

        Db::update($type->table, [...$data, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $this->id]);

        foreach ($data as $key => $value) {
            $this->{Safe::camelcase($key)} = $value;
        }

        Activity::changes($this->table, $this->id, $before, [...$data, 'slug' => $this->slug]);

        return true;
    }

    /**
     * Create a draft copy of the post: the same content, parent, discussion and fields,
     * the title with "(copy)" and a new slug made from it.
     *
     * @return Post|null Null when the copy was not saved.
     */
    public function duplicate(): ?Post
    {
        $copy = self::add($this->type, [
            'title'      => t(':title (copy)', $this->title),
            'content'    => $this->content,
            'parent_id'  => $this->parentId,
            'position'   => $this->position,
            'status'     => 'draft',
            'discussion' => $this->discussion,
            'password'   => $this->password,
        ]);
        if (! $copy instanceof Post) {
            return null;
        }

        $fields = Db::select("{$this->table}_fields", ['key', 'value'], ['post_id' => $this->id]) ?: [];
        if ($fields) {
            Db::insert("{$this->table}_fields", array_map(fn (array $field) => ['post_id' => $copy->id, ...$field], $fields));
        }

        Activity::record($this->table, $this->id, 'duplicated', ['id' => $copy->id, 'title' => $copy->title]);

        return $copy;
    }

    /**
     * Whether the post takes new comments: its discussion is not closed, and the post is not older
     * than the days of the Discussions settings when they close the comments on old posts.
     *
     * @return bool
     */
    public function isDiscussionOpen(): bool
    {
        if ($this->discussion === 'closed') {
            return false;
        }

        if ($this->createdAt === '' || ! Option::get('comments.close_comments_for_old_posts', false)) {
            return true;
        }

        return strtotime($this->createdAt) > time() - 86400 * max(1, (int) Option::get('comments.close_comments_days_old', 14));
    }

    /**
     * Whether the post is in the trash.
     *
     * @return bool
     */
    public function isTrashed(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Whether delete() moves the post to the trash: its type has one and the post is not in it yet.
     *
     * @return bool
     */
    public function canTrash(): bool
    {
        return $this->softDelete && ! $this->isTrashed();
    }

    /**
     * Delete the post: move it to the trash when canTrash(), otherwise delete it permanently.
     *
     * @return bool False when there was no such row.
     */
    public function delete(): bool
    {
        if (! $this->canTrash()) {
            return $this->forceDelete();
        }

        $deletedAt = date('Y-m-d H:i:s');
        $updated   = Db::update($this->table, [$this->deletedAtColumn => $deletedAt], ['id' => $this->id])?->rowCount() > 0;
        if ($updated) {
            $this->deletedAt = $deletedAt;
            Activity::record($this->table, $this->id, 'trashed');
        }

        return $updated;
    }

    /**
     * Take the post out of the trash.
     *
     * @return bool False when the post is not in the trash.
     */
    public function restore(): bool
    {
        if (! $this->isTrashed()) {
            return false;
        }

        $restored = Db::update($this->table, [$this->deletedAtColumn => null], ['id' => $this->id])?->rowCount() > 0;
        if ($restored) {
            $this->deletedAt = null;
            Activity::record($this->table, $this->id, 'restored');
        }

        return $restored;
    }

    /**
     * Delete the post permanently with its slug and comments, bypassing the trash; its fields go with it by
     * the foreign key, its children move up to its parent. Comments point at a post of any type, so no foreign
     * key can delete them.
     *
     * @return bool False when there was no such row.
     */
    public function forceDelete(): bool
    {
        $deleted = Db::delete($this->table, ['id' => $this->id])?->rowCount() > 0;
        if ($deleted) {
            Db::delete('slugs', ['entity_id' => $this->id, 'entity_table' => $this->table]);
            $comments = Db::select('comments', 'id [Int]', ['post_type' => $this->type, 'post_id' => $this->id]) ?? [];
            if ($comments !== []) {
                Db::delete('comments', ['id' => $comments]);
                Db::delete('activity', ['entity_table' => 'comments', 'entity_id' => $comments]);
            }
            Db::update($this->table, ['parent_id' => $this->parentId], ['parent_id' => $this->id]);
            Activity::forget($this->table, $this->id);
        }

        return $deleted;
    }

    /**
     * Whether a post can be the parent of this one: the type is hierarchical, the parent is a post
     * of the same type and neither this post nor one of its descendants, so no loop appears.
     *
     * @param int $parentId `0` for no parent, always allowed.
     * @return bool
     */
    public function canHaveParent(int $parentId): bool
    {
        if ($parentId === 0) {
            return true;
        }

        if (! (Type::get($this->type)?->hierarchical ?? false)) {
            return false;
        }

        // up from the parent to the root: meeting this post means the parent is its descendant
        $seen = [];
        for ($id = $parentId; $id > 0; $id = (int) $next) {
            if ($id === $this->id || isset($seen[$id])) {
                return false;
            }

            $seen[$id] = true;
            $next      = Db::get($this->table, 'parent_id', ['id' => $id]);
            if (! is_scalar($next)) {
                return $id !== $parentId;
            }
        }

        return true;
    }
}
