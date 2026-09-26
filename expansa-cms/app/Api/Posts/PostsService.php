<?php

declare(strict_types=1);

namespace App\Api\Posts;

use App\Api\Files\FilesService;
use App\Models\Post;
use App\Query\Query;
use Expansa\Facades\Csv;
use Expansa\Facades\Json;
use Expansa\Facades\View;
use Expansa\Http\Response;

final class PostsService
{
    public function list(): array
    {
        return Query::apply([
            'type'     => 'pages',
            'page'     => 1,
            'per_page' => 30,
        ]);
    }

    /**
     * Returns a raw file-download response — not JSON, so the controller returns this
     * Response object directly rather than a plain array (Kernel sends it as-is).
     */
    public function export(array $input): Response
    {
        $format = trim(strval($input['format'] ?? ''));
        $types  = $input['types'] ?? [];
        $types  = is_array($types) ? $types : [];
        $date   = date('YmdHis');

        $content = Query::apply(
            ['type' => $types, 'per_page' => 99999999],
            function ($posts) use ($format) {
                if (!is_array($posts) || empty($posts)) {
                    return $posts;
                }

                return match ($format) {
                    'json'  => Json::encode($posts),
                    // the BOM makes Excel read the file as UTF-8 instead of the system codepage
                    'csv'   => "\xEF\xBB\xBF" . Csv::encode([array_keys($posts[0]), ...$posts]),
                    default => $posts,
                };
            }
        );

        return new Response(is_string($content) ? $content : Json::encode($content), headers: [
            'Content-Type'        => 'application/force-download',
            'Content-Disposition' => sprintf('inline; filename="core-posts-%s.%s"', $date, $format),
        ]);
    }

    public function import(array $input): array
    {
        $imported = [];
        $filename = $input['filename'] ?? '';
        $map      = $input['map'] ?? [];
        $status   = $input['status'] ?? '';
        $author   = $input['author'] ?? '';
        $type     = $input['type'] ?? '';
        $encoding = FilesService::csvEncoding($input['encoding'] ?? null);

        if (file_exists($filename)) {
            foreach (Csv::iterate($filename, encoding: $encoding) as $row) {
                $args = array_filter(array_combine($map, $row), fn($key) => !empty($key), ARRAY_FILTER_USE_KEY);

                $rowStatus = trim(strval($row['status'] ?? $status));
                $rowAuthor = trim(strval($row['author'] ?? $author));
                if (!empty($rowStatus)) {
                    $args['status'] = $rowStatus;
                }

                if (!empty($rowAuthor)) {
                    $args['author'] = $rowAuthor;
                }

                $postId = Post::add($type, $args);
                if ($postId) {
                    $imported[] = $postId;
                }
            }
        }

        return [
            'completed' => true,
            'output'    => View::make(EX_DASHBOARD . 'views/global/state', [
                'icon'        => 'success',
                'title'       => t('Import is complete!'),
                'description' => t(':counts posts were imported successfully. Do you want to [start another import](:link)?', count($imported), url('/dashboard/import')),
            ]),
        ];
    }
}
