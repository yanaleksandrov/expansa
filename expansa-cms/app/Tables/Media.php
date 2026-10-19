<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Media extends Table
{
    public function data(): array
    {
        return [];
    }

    public function cells(): array
    {
        return [
            $this->cell('media')->view('media'),
        ];
    }

    /**
     * The library loads the files itself, see MediaService.
     *
     * @return int
     */
    public function perPage(): int
    {
        return 0;
    }

    public function headData(): array
    {
        return [
            'title'    => t('Media Library'),
            'uploader' => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'icon'        => 'no-media',
            'title'       => t('No files found in the library'),
            'description' => t('Either nothing has been uploaded yet or no files match the current filters.'),
            'buttons'     => [
                [
                    'label' => '<i class="ph ph-plus"></i> ' . t('Add new files'),
                    'url'   => url('/dashboard/import'),
                ],
            ],
        ];
    }
}
