<?php

namespace dashboard\app\Api;

use app\View;
use Dashboard\Form;
use Expansa\Csv;
use Expansa\Disk;
use Expansa\Safe;

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
                $uploadedFile = Disk::file()->upload($file)->move(EX_UPLOADS . 'i/');

                if (! $uploadedFile instanceof File) {
                    continue;
                }

                $filepath = Safe::path($uploadedFile->path ?? '');
                $rows     = Csv::decode($filepath);

                View::get(
                    EX_DASHBOARD . 'forms/posts-import-fields',
                    [
                        'samples'  => $rows[0] ?? [],
                        'filepath' => $filepath,
                    ]
                );

                return [
                    'fields' => Form::get(EX_DASHBOARD . 'forms/expansa-post-import-fields.php', true),
                ];
            }
        }
        return [];
    }
}
