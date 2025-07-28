<?php

declare(strict_types=1);

namespace App\Api;

use Expansa\Facades\Safe;

class Post
{
    /**
     * Get all items.
     *
     * @url    GET api/posts
     */
    public function index(): array
    {
        return [
            'method' => 'PUT update user by ID',
        ];
    }

    /**
     * Create item.
     *
     * @url POST api/posts
     */
    public function create(): array
    {
        $data = Safe::data(
            $_POST ?? [],
            [
                'post-type' => 'text',
                'title'     => 'text',
                'status'    => 'text',
            ]
        )->apply();

        [$type, $args] = [array_shift($data), $data];

        if (!$type) {
            return [
                'method' => t('Post type is missing'),
            ];
        }

        \App\Post::add($type, $args);

        return [
            'method' => 'POST create user',
        ];
    }

    /**
     * Update item by ID.
     *
     * @url    PUT api/posts/$id
     */
    public function update(): array
    {
        return [
            'method' => 'PUT update user by ID',
        ];
    }

    /**
     * Remove item by ID.
     *
     * @url    DELETE api/posts/$id
     */
    public function delete(): array
    {
        return [
            'method' => 'DELETE remove user by ID',
        ];
    }
}
