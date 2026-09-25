<?php

declare(strict_types=1);

namespace App\Api\Files;

use Expansa\Facades\Csv;
use Expansa\Facades\Disk;
use Expansa\Facades\Safe;
use Expansa\Filesystem\File;

/**
 * Handles the CSV file upload step of the posts importer (dashboard/forms/posts-import.php,
 * `$ajax.post("files/upload")`, which expects `response.fields`).
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
    /**
     * CSV encodings offered by the importer; 'auto' lets the codec choose between UTF-8 and Windows-1251.
     * Keys are passed to Csv as is, values are labels.
     */
    public const array CSV_ENCODINGS = [
        'auto'         => 'Auto-detect (UTF-8, Windows-1251)',
        'UTF-8'        => 'UTF-8',
        'Windows-1251' => 'Windows-1251 (Cyrillic)',
        'KOI8-R'       => 'KOI8-R (Cyrillic)',
        'Windows-1252' => 'Windows-1252 (Western European)',
        'GBK'          => 'GBK (Simplified Chinese)',
        'GB18030'      => 'GB18030 (Simplified Chinese)',
        'BIG5'         => 'Big5 (Traditional Chinese)',
        'CP932'        => 'Shift_JIS (Japanese)',
        'EUC-KR'       => 'EUC-KR (Korean)',
    ];

    /**
     * Returns the encoding if it is offered by the importer, otherwise 'auto'.
     *
     * @param mixed $encoding Raw request value.
     * @return string
     */
    public static function csvEncoding(mixed $encoding): string
    {
        return is_string($encoding) && isset(self::CSV_ENCODINGS[$encoding]) ? $encoding : 'auto';
    }

    /**
     * Stores the uploaded CSV file and renders the column mapping form with its first row as samples.
     *
     * @param array $files    Uploaded files from $_FILES.
     * @param mixed $encoding Encoding chosen in the form, see CSV_ENCODINGS.
     * @return array
     */
    public function upload(array $files, mixed $encoding = 'auto'): array
    {
        foreach ($files as $file) {
            $uploadedFile = Disk::file('')->upload($file)->move(EX_STORAGE . 'i/');

            if (!$uploadedFile instanceof File) {
                continue;
            }

            $filepath = Safe::path($uploadedFile->path ?? '');
            $samples  = Csv::iterate($filepath, encoding: self::csvEncoding($encoding))->current() ?? [];

            $fields = view(EX_DASHBOARD . 'forms/posts-import-fields', [
                'samples'  => $samples,
                'filepath' => $filepath,
            ])->render();

            return ['fields' => $fields];
        }

        return [];
    }
}
