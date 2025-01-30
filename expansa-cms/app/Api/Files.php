<?php

namespace App\Api;

use Expansa\Facades\Csv;
use Expansa\Facades\Disk;
use Expansa\Facades\Safe;

class Files
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'files';

    /**
     * Upload files from external url.
     *
     * @since 2025.1
     */
    public static function upload(): File|array
    {
        $files = $_FILES ?? [];
        if ($files) {
            foreach ($files as $file) {
                $uploadedFile = Disk::file('')->upload($file)->move(EX_STORAGE . 'i/');

                if (! $uploadedFile instanceof File) {
                    continue;
                }

                $filepath = Safe::path($uploadedFile->path ?? '');
                $rows     = Csv::decode($filepath);

                echo view(
                    EX_DASHBOARD . 'forms/posts-import-fields',
                    [
                        'samples'  => $rows[0] ?? [],
                        'filepath' => $filepath,
                    ]
                );

                return [
                    'fields' => form(EX_DASHBOARD . 'forms/expansa-post-import-fields.php', true),
                ];
            }
        }
        return [];
    }
}
