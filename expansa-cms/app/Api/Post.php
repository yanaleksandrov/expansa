<?php

declare(strict_types=1);

namespace App\Api;

use Expansa\Facades\Safe;
use PHPMailer\PHPMailer\Exception;

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
     * @url    POST api/posts
     * @throws Exception
     */
    public function create(): array
    {
        $fields = Safe::data(
            $_POST ?? [],
            [
                'limits'     => 'absint',
                'period'     => 'text',
                'start-date' => 'datetime',
                'end-date'   => 'datetime',
                'sites'      => 'trim|sitesList',
            ]
        )->extend('sitesList', function ($value) {
            $sitesList = array_map('trim', explode(',', $value));

            return array_filter($sitesList, fn($url) => filter_var($url, FILTER_VALIDATE_URL));
        })->apply();

        $title  = Safe::text($_POST['title'] ?? '');
        $status = Safe::text($_POST['status'] ?? '');
        $type   = Safe::text($_POST['post-type'] ?? '');
        if (! $type) {
            throw new Exception(t('Post type is missing'));
        }

        echo '<pre>';
        print_r($_REQUEST);
        echo '</pre>';
        $post = \App\Post::add($type, compact('title', 'status', 'fields'));
        echo '<pre>';
        var_dump($fields);
        var_dump($post);
        echo '</pre>';

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
