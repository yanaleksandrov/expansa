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

    public function headData(): array
    {
        return [
            'title'    => t('Media Library'),
            'actions'  => false,
            'filter'   => false,
            'uploader' => true,
            'show'     => 'false',
            'content'  => '',
        ];
    }

    public function notFoundData(): array
    {
        return [
            'icon'        => 'no-media',
            'title'       => t('No files found in the library'),
            'description' => t('Either nothing has been uploaded yet or no files match the current filters'),
        ];
    }
}
