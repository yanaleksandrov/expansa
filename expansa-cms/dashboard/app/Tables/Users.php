<?php

declare(strict_types=1);

namespace Dashboard\Tables;

use Dashboard\Table\Cell;
use Dashboard\Table\Row;
use Expansa\Hook;
use Expansa\Url;

final class Users
{
    public function data(): array
    {
        Hook::add('expansa_dashboard_data', function ($data) {
            $data['items'] = [
                [
                    'cb'     => '<input type="checkbox" name="post[]" x-bind="switcher">',
                    'avatar' => 'https://i.pravatar.cc/150?img=1',
                    'name'   => 'Izabella Tabakova',
                    'email'  => 'codyshop@team.com',
                    'role'   => 'Admin',
                    'visit'  => '3 days ago',
                ],
            ];
            return $data;
        });

        return [ 546 ];
    }

    public function rows(): array
    {
        return [
            Row::add()->attribute('class', 'table__row'),
        ];
    }

    public function columns(): array
    {
        return [
            Cell::add('cb')->title('<input type="checkbox" x-bind="trigger" />')->fixedWidth('1rem')->view('cb'),
            Cell::add('image')->fixedWidth('2.5rem')->view('image'),
            Cell::add('name')->title(t('Name'))->flexibleWidth('16rem')->sortable()->view('title'),
            Cell::add('role')->title(t('Role'))->fixedWidth('6rem')->view('raw'),
            Cell::add('visit')->title(t('Last visit'))->fixedWidth('6rem')->view('raw'),
        ];
    }

    public function attributes(): array
    {
        return [
            'class'   => 'table',
            'x-data'  => 'table',
            '@change' => '$ajax("users/get").then(response => items = response.items)',
        ];
    }

    public function headerContent(): array
    {
        return [
            'title'   => t('Users'),
            'actions' => true,
            'filter'  => true,
        ];
    }

    public function notFoundContent(): array
    {
        return [
            'title'       => t('Users not found'),
            'description' => t('You don\'t have any users yet. <a @click="$dialog.open(\'tmpl-post-editor\', postEditorDialog)">Add them manually</a> or [import via CSV](:importLink)', Url::site('/dashboard/import')),
        ];
    }
}
