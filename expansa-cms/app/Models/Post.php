<?php

declare(strict_types=1);

namespace App\Models;

use App\Post\Type;
use Expansa\Database\FieldEav;
use Expansa\Database\Model;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use LogicException;

/**
 * Class representing an entry entity.
 */
class Post
{
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
         * Visibility status: publish, pending, draft, protected, private, trash or future.
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
    )
    {
        $this->table = Safe::tablename($this->type);
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
                'content'    => 'text',
                'author_id'  => 'absint:' . ($user->id ?? '0'),
                'parent_id'  => 'absint:0',
                'comments'   => 'absint:0',
                'views'      => 'absint:0',
                'position'   => 'absint:0',
                'status'     => 'text:draft',
                'discussion' => 'bool:open',
                'password'   => 'text',
            ]
        )->apply();

        // insert to DB
        Db::insert($type->table, array_diff_key($data, array_flip([ 'slug', 'fields' ])));

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
                    'slug'         => Safe::slug($args['slug'] ?? $data['title']),
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
        if ($slug instanceof Slug) {
            return self::get(Safe::tablename($slug->entityTable), $slug->entityId);
        }
        return null;
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

            $data['slug'] = $type->public === true ? Slug::get($data['id'], $type->table) : '';

            return new Post(...$data);
        }

        return null;
    }

    /**
     * Remove post by field.
     *
     * @param string $type
     * @param mixed $value
     * @param string $by
     * @return bool
     */
    public static function delete(string $type, mixed $value, string $by = 'id'): bool
    {
        return Db::delete($type, [ $by => $value ])->rowCount() > 0;
    }
}
