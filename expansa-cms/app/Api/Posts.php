<?php

declare(strict_types=1);

namespace App\Api;

use App\Query\Query;
use App\Post;
use Expansa\Codecs\Csv;
use Expansa\Facades\Json;
use Expansa\Facades\View;

class Posts
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'posts';

    /**
     * Create item.
     *
     * @url    POST api/posts
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
     * @url    GET api/posts
     */
    public function index(): array
    {
        return Query::apply(
            [
                'type'     => 'pages',
                'page'     => 1,
                'per_page' => 30,
            ]
        );
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

    /**
     * Export new posts.
     *
     * @since 2025.1
     */
    public static function export()
    {
        $format = trim(strval($_REQUEST['format'] ?? ''));
        $types  = $_REQUEST['types'] ?? [];
        $date   = date('YmdHis');

        header('Content-type: application/force-download');
        header('Content-Disposition: inline; filename="core-posts-' . $date . '.' . $format . '"');

        if (! is_array($types)) {
            exit;
        }

        echo Query::apply(
            [
                'type'     => $types,
                'per_page' => 99999999,
            ],
            function ($posts) use ($format) {
                if (! is_array($posts) || empty($posts)) {
                    return $posts;
                }

                switch ($format) {
                    case 'json':
                        return Json::encode($posts);
                    case 'csv':
                        array_unshift($posts, array_keys($posts[0]));

                        return Csv::export($posts);
                    default:
                        return $posts;
                }
            }
        );

        exit;
    }

    /**
     * Import posts.
     *
     * @since 2025.1
     */
    public static function import(): array
    {
        $imported = [];
        $filename = $_POST['filename'] ?? '';
        $map      = $_POST['map'] ?? [];
        $status   = $_POST['status'] ?? '';
        $author   = $_POST['author'] ?? '';
        $type     = $_POST['type'] ?? '';

        if (file_exists($filename)) {
            $rows = Csv::import($filename);
            foreach ($rows as $row) {
                $args = array_filter(array_combine($map, $row), fn ($key) => ! empty($key), ARRAY_FILTER_USE_KEY);

                $status = trim(strval($row['status'] ?? $status));
                $author = trim(strval($row['author'] ?? $author));
                if (! empty($status)) {
                    $args['status'] = $status;
                }

                if (! empty($author)) {
                    $args['author'] = $author;
                }

                $post_id = Post::add($type, $args);
                if ($post_id) {
                    $imported[] = $post_id;
                }
            }
        }

        return [
            'completed' => true,
            'output'    => View::make(EX_DASHBOARD . 'views/global/state', [
                'icon'        => 'success',
                'title'       => t('Import is complete!'),
                'description' => t(':counts posts was successfully imported. Do you want [to launch a new import?](:link)', count($imported), url('/dashboard/import')),
            ]),
        ];
    }
}
