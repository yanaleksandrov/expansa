<?php

declare(strict_types=1);

namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;
use Expansa\I18n;

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
            'title'       => I18n::_t('Files in library is not found'),
            'description' => I18n::_t('They have not been uploaded or do not match the filter parameters'),
        ];
    }

    public function headerContent(): array
    {
        return [
            'title'    => I18n::_t('Media Library'),
            'actions'  => false,
            'filter'   => false,
            'uploader' => true,
            'show'     => 'false',
            'content'  => '',
        ];
    }
}
