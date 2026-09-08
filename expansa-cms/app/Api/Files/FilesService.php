<?php

declare(strict_types=1);

namespace App\Api\Files;

use Expansa\Facades\Csv;
use Expansa\Facades\Disk;
use Expansa\Facades\Safe;
use Expansa\Filesystem\File;

/**
 * Handles the CSV file upload step of the posts importer (dashboard/forms/posts-import.php,
 * `$ajax("files/upload")`, which expects `response.fields`).
 *
 * The old code both `echo`'d the rendered field-mapping form (view()) and then, separately,
 * tried to build it again through form() — which doesn't accept the samples/filepath data
 * posts-import-fields.php needs, and pointed at a file that doesn't exist
 * (expansa-post-import-fields.php; the real one is posts-import-fields.php). Only the
 * view() render actually works; it's captured as a string and returned instead of echoed,
 * since that's what the frontend reads as `response.fields`.
 */
final class FilesService
{
    public function upload(array $files): array
    {
        foreach ($files as $file) {
            $uploadedFile = Disk::file('')->upload($file)->move(EX_STORAGE . 'i/');

            if (!$uploadedFile instanceof File) {
                continue;
            }

            $filepath = Safe::path($uploadedFile->path ?? '');
            $rows     = Csv::decode($filepath);

            $fields = view(EX_DASHBOARD . 'forms/posts-import-fields', [
                'samples'  => $rows[0] ?? [],
                'filepath' => $filepath,
            ])->render();

            return ['fields' => $fields];
        }

        return [];
    }
}
