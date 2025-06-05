<?php

namespace App\Api;

use Expansa\Support\Arr;

class Option
{
    /**
     * Create item.
     *
     * @url    POST api/user
     */
    public function create(): array
    {
        return [
            'method' => 'POST create user',
        ];
    }

    /**
     * Get all items.
     *
     * @url    GET api/user
     */
    public function index(): array
    {
        return [
            'method' => 'GET user list',
        ];
    }

    /**
     * Update item by ID.
     *
     * @url    PUT api/user/$id
     */
    public function update(): array
    {
        $options = Arr::exclude($_POST, [ 'nonce' ]);
        if ($options) {
            foreach ($options as $option => $value) {
                \App\Option::update($option, $value);
            }
        }

        return [
            [
                'target' => 'body',
                'notify' => t('Options is updated successfully'),
            ],
        ];
    }

    /**
     * Remove item by ID.
     *
     * @url    DELETE api/user/$id
     */
    public function delete(): array
    {
        return [
            'method' => 'DELETE remove user by ID',
        ];
    }
}
