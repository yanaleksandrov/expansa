<?php

namespace dashboard\app\Api;

use Expansa\Error;
use Expansa\I18n;
use Expansa\Safe;

class Post
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'post';

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

        $title  = Safe::text($_POST['app-name'] ?? '');
        $status = Safe::text($_POST['status'] ?? '');
        $type   = Safe::text($_POST['post-type'] ?? '');
        if (! $type) {
            return Error::add('post-type-create', I18n::_t('Post type is missing'));
        }

        // TODO:: title make unique by user for "api-keys" post type
//      $user = User::current();
//      if ( $user instanceof User ) {
//          $suffix = 1;
//          while ( Expansa\Post::getByTitle( $type, $title ) instanceof Expansa\Post ) {
//              $title = sprintf( '%s %s', $title, $suffix++ );
//          }
//      }

        $post = \app\Post::add($type, compact('title', 'status', 'fields'));
        echo '<pre>';
        print_r($fields);
        print_r($post);
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
