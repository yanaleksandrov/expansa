<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Status;
use App\Post\Type;
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
                'discussion' => 'text:open',
                'password'   => 'text',
            ]
        )->apply();

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

    public static function getBySlug(string $value): ?Post
    {
        $slug = Slug::find($value, 'slug');
        $post = $slug instanceof Slug ? self::get(Safe::tablename($slug->entityTable), $slug->entityId) : null;

        return $post?->isTrashed() ? null : $post;
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
     * @param array<string, mixed> $args `title`, `content`, `parent_id`, `status`, `discussion`, `password`, `slug`.
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
            'status'     => 'text:draft',
            'discussion' => 'text:open',
            'password'   => 'text',
        ];

        $data = Safe::data(array_intersect_key($args, $rules), array_intersect_key($rules, $args))->apply();

        // a post can not be the parent of itself
        if (($data['parent_id'] ?? 0) === $this->id) {
            $data['parent_id'] = 0;
        }

        if (isset($data['status']) && ! array_key_exists($data['status'], Status::getStatuses())) {
            unset($data['status']);
        }

        if (isset($data['discussion']) && ! in_array($data['discussion'], ['open', 'closed'], true)) {
            unset($data['discussion']);
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

        return $copy;
    }

    /**
     * Whether the post takes new comments: its discussion is not closed.
     *
     * @return bool
     */
    public function isDiscussionOpen(): bool
    {
        return $this->discussion !== 'closed';
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
        }

        return $restored;
    }

    /**
     * Delete the post permanently with its slug, bypassing the trash; its fields go with it by the foreign key.
     *
     * @return bool False when there was no such row.
     */
    public function forceDelete(): bool
    {
        $deleted = Db::delete($this->table, ['id' => $this->id])?->rowCount() > 0;
        if ($deleted) {
            Db::delete('slugs', ['entity_id' => $this->id, 'entity_table' => $this->table]);
        }

        return $deleted;
    }
}
