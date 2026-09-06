<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Users extends Table
{
    public function data(): array
    {
        return [
            [
                'id'     => 1,
                'image'  => 'https://i.pravatar.cc/150?img=1',
                'name'   => 'Izabella Tabakova',
                'email'  => 'codyshop@team.com',
                'status' => 'Active',
                'role'   => 'admin',
                'visit'  => '3 days ago',
            ],
            [
                'id'     => 1,
                'image'  => 'https://i.pravatar.cc/150?img=2',
                'name'   => 'Izabella Tabakova',
                'email'  => 'codyshop@team.com',
                'status' => 'Active',
                'role'   => 'subscriber',
                'visit'  => '3 days ago',
            ],
            [
                'id'     => 1,
                'image'  => 'https://i.pravatar.cc/150?img=3',
                'name'   => 'Izabella Tabakova',
                'email'  => 'codyshop@team.com',
                'status' => 'Active',
                'role'   => 'editor',
                'visit'  => '3 days ago',
            ],
            [
                'id'     => 1,
                'image'  => 'https://i.pravatar.cc/150?img=4',
                'name'   => 'Izabella Tabakova',
                'email'  => 'codyshop@team.com',
                'status' => 'Active',
                'role'   => 'author',
                'visit'  => '3 days ago',
            ],
        ];
    }

    public function cells(): array
    {
        return [
            $this->cell('id')->title('<input type="checkbox" u-bind="trigger" />')->fixedWidth('1rem')->view('cb'),
            $this->cell('image')->fixedWidth('2.5rem')->view('image'),
            $this->cell('name')->title(t('Name'))->flexibleWidth('16rem')->sortable()->view('title'),
            $this->cell('status')->title(t('Status'))->fixedWidth('6rem')->view('raw'),
            $this->cell('visit')->title(t('Last visit'))->fixedWidth('8rem')->view('raw'),
            $this->cell('role')->title(t('Role'))->fixedWidth('8rem')->view('role'),
        ];
    }

    public function headData(): array
    {
        return [
            'title'   => t('Users'),
            'actions' => true,
            'filter'  => true,
        ];
    }

    public function notFoundData(): array
    {
        return [
            'title'       => t('Users not found'),
            'description' => t('You don&apos;t have any users yet. <a @click="$dialog.open(`tmpl-post-editor`, postEditorDialog)">Add them manually</a> or [import via CSV](:importLink)', url('/dashboard/import')),
        ];
    }
}
