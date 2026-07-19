<?php

declare(strict_types=1);

namespace App\Api;

use App\Models\Field;
use App\Models\Post;
use DateTime;
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

        $post = \App\Models\Apikey::get(2);
        //print_r($post->field->limits);

        $start = microtime(true);
        for ($i = 0; $i < 1; $i++) {
            //$fields = $post->field->add(['mytostsss' => 'Hello World!']);
            var_dump($post->field->delete('terter'));
        }
        $end = microtime(true);

        $elapsed = $end - $start;
        echo "Elapsed time: $elapsed seconds\n";
        exit;

        $post = Post::add('api-keys', compact('title', 'status', 'fields'));

        if ($post instanceof Post) {
            $postData = [];
            foreach ((array) $post as $key => $value) {
                if (!in_array($key, ['uuid', 'title', 'status', 'createdAt', 'updatedAt'], true)) {
                    continue;
                }

                if (in_array($key, ['createdAt', 'updatedAt'], true)) {
                    $date = new \DateTime($value);
                    if ($date instanceof \DateTime) {
                        $value = $date->format('j F, Y');
                    }
                }

                $postData[$key] = $value;
            }

            $fields = (new Field($post))->get();
            print_r($post);
            print_r($fields);
            if ($fields) {
                foreach ($fields as $field => $values) {
                    $key = Safe::camelcase($field);
                    if (!isset($key, $values[0])) {
                        continue;
                    }

                    if (in_array($key, ['endDate', 'startDate'], true)) {
                        $date = \DateTime::createFromFormat('Y-m-d', $values[0]);
                        if ($date instanceof \DateTime) {
                            $values[0] = $date->format('j F, Y');
                        }
                    }

                    $postData[$key] = $values[0];
                }
            }

            echo Json::encode([
                'post' => $postData,
                'data' => [
                    [
                        'target' => 'body',
                        'notify' => t('New API key is created'),
                    ],
                ],
            ]);
            exit;
        }

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
