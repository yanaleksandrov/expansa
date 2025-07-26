<?php

declare(strict_types=1);

namespace App;

use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use LogicException;

class Post
{
    /**
     * Class representing an entry entity.
     *
     * @property int    $id           Unique identifier of the entry.
     * @property string $title        The title of the entry.
     * @property string $content      The content of the entry.
     * @property int    $authorId     The ID of the author of the entry.
     * @property int    $parentId     The ID of the parent entry.
     * @property int    $comments     The number of comments.
     * @property int    $views        The number of views.
     * @property int    $position     The position of the entry (for sorting).
     * @property string $status       The status of the entry.
     * @property string $discussion   Post discussion status.
     * @property string $password     The password for protecting the entry.
     * @property string $createdAt    The creation date and time of the entry.
     * @property string $updatedAt    The last update date and time of the entry.
     * @property string $type         The type of the entry (e.g., "article", "page", etc.).
     * @property string $table        The name of the database table.
     * @property string $uuid         The unique string ID slug for the post.
     * @property string $link         The full URL of the entry.
     * @property string $slug         The unique URL slug for the entry.
     * @property array  $fields       Additional custom fields.
     */
    private function __construct(
        public int $id = 0,
        public string $title = '',
        public string $content = '',
        public int $authorId = 0,
        public int $parentId = 0,
        public int $comments = 0,
        public int $views = 0,
        public int $position = 0,
        public string $status = 'pending',
        public string $discussion = 'open',
        public string $password = '',
        public string $createdAt = '',
        public string $updatedAt = '',
        public string $type = '',
        public string $table = '',
        public string $uuid = '',
        public string $link = '',
        public string $slug = '',
        public array $fields = []
    ) {} // phpcs:ignore

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
                $slug = Slug::add($post->id, $type->table, Safe::slug($args['slug'] ?? $data['title']));
                if ($slug) {
                    $slug = Slug::get($slug);

                    $post->slug = $slug['slug'];
                    $post->uuid = $slug['uuid'];
                    $post->link = '';
                }
            }

            $fields = Safe::array($args['fields'] ?? []);
            print_r($fields);
            if ($fields) {
                ( new Field($post) )->import($fields);
            }
        }

        return $post;
    }

    public static function getBySlug(string $value): ?Post
    {
        $slug = Slug::get($value);
        if (! empty($slug['entity_table'])) {
            return self::get(Safe::tablename($slug['entity_table']), $slug['entity_id']);
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

            $data['slug']   = $type->public === true ? Slug::find($data['id'], $type->table) : '';
            $data['fields'] = [];

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

    public static function update(int $id, string $type, array $args)
    {
    }
}
