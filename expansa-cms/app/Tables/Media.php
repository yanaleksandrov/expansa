<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table\Cell;
use Expansa\Builders\Table\Row;

final class Media
{
    public function tag(): string
    {
        return '';
    }

    public function rows(): array
    {
        return [
            Row::add()->tag(''),
        ];
    }

    public function dataBefore(): string
    {
        return '<div class="storage" x-storage>';
    }

    public function dataAfter(): string
    {
        return '</div>';
    }

    public function columns(): array
    {
        return [
            Cell::add('media')->view('media'),
        ];
    }

    public function attributes(): array
    {
        return [
            'class' => 'table',
        ];
    }

    public function notFoundContent(): array
    {
        return [
            'icon'        => 'no-media',
            'title'       => t('Files in library is not found'),
            'description' => t('They have not been uploaded or do not match the filter parameters'),
        ];
    }

    public function headerContent(): array
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
}
