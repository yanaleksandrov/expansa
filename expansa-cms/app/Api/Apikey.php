<?php

declare(strict_types=1);

namespace App\Api;

use DateTime;
use App\Post;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Support\Str;

class Apikey
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
     * @url POST api/apikey
     */
    public function create(): array
    {
        $fields = Safe::data(
            $_POST ?? [],
            [
                'limits'     => 'absint',
                'period'     => 'text',
                'start-date' => 'date',
                'end-date'   => 'date',
                'sites'      => 'trim|sitesList',
                'token'      => 'trim:' . (Str::random(32)),
            ]
        )->extend('date', function ($value) {
            $date = DateTime::createFromFormat('j F, Y', $value);
            if ($date instanceof DateTime) {
                return $date->format('Y-m-d');
            }
            return '';
        })->extend('sitesList', function ($value) {
            $sitesList = array_map('trim', explode(',', $value));

            return array_filter($sitesList, fn($url) => filter_var($url, FILTER_VALIDATE_URL));
        })->apply();

        $title  = Safe::text($_POST['title'] ?? '');
        $status = Safe::text($_POST['status'] ?? '');

        $post = Post::add('api-keys', compact('title', 'status', 'fields'));
        if ($post instanceof Post) {
            echo Json::encode([
                'post' => $post,
                'data' => [
                    [
                        'target' => 'body',
                        'notify' => t('New API key is created'),
                    ],
                ],
            ]);
            exit;
        }
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
