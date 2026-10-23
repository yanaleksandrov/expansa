<?php

declare(strict_types=1);

namespace App\Api\Posts;

use App\Api\Files\FilesService;
use App\Models\Post;
use App\Post\Type;
use Expansa\Facades\Csv;
use Expansa\Facades\Db;
use Expansa\Facades\Json;
use Expansa\Facades\View;
use Expansa\Http\Request;
use Expansa\Http\Response;

final class PostsService
{
    /**
     * Export the posts of the chosen types out of the trash as a JSON or CSV download.
     */
    public function export(Request $request, Response $response): Response
    {
        $format = trim(strval($request->input['format'] ?? ''));
        $types  = $request->input['types'] ?? [];
        $types  = is_array($types) ? $types : [];
        $date   = date('YmdHis');

        $posts = [];
        foreach ($types as $key) {
            $type = Type::get((string) $key);
            if ($type instanceof Type) {
                $rows  = Db::select($type->table, '*', $type->canTrash ? ['deleted_at' => null] : []) ?? [];
                $posts = [...$posts, ...array_map(fn (array $row) => ['type' => $type->key, ...$row], $rows)];
            }
        }

        $response->content = match (true) {
            $posts === []    => Json::encode([]),
            // the BOM makes Excel read the file as UTF-8 instead of the system codepage
            $format === 'csv' => "\xEF\xBB\xBF" . Csv::encode([array_keys($posts[0]), ...$posts]),
            default           => Json::encode($posts),
        };

        return $response
            ->setHeader('Content-Type', 'application/force-download')
            ->setHeader('Content-Disposition', sprintf('inline; filename="core-posts-%s.%s"', $date, $format));
    }

    public function import(Request $request): array
    {
        $imported = [];
        $filename = $request->post['filename'] ?? '';
        $map      = $request->post['map'] ?? [];
        $status   = $request->post['status'] ?? '';
        $author   = $request->post['author'] ?? '';
        $type     = $request->post['type'] ?? '';
        $encoding = FilesService::csvEncoding($request->post['encoding'] ?? null);

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
            'output'    => View::create(EX_DASHBOARD . 'views/parts/state', [
                'icon'        => 'success',
                'title'       => t('Import Is Complete'),
                'description' => t(':counts posts were imported successfully. Do you want to [start another import](:link)?', count($imported), url('/dashboard/import')),
            ]),
        ];
    }
}
