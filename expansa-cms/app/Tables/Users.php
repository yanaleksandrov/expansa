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
                'cb'     => '<input type="checkbox" name="post[]" x-bind="switcher">',
                'avatar' => 'https://i.pravatar.cc/150?img=1',
                'name'   => 'Izabella Tabakova',
                'email'  => 'codyshop@team.com',
                'role'   => 'Admin',
                'visit'  => '3 days ago',
            ],
        ];
    }

    public function cells(): array
    {
        return [
            $this->cell('cb')->title('<input type="checkbox" x-bind="trigger" />')->fixedWidth('1rem')->view('cb'),
            $this->cell('image')->fixedWidth('2.5rem')->view('image'),
            $this->cell('name')->title(t('Name'))->flexibleWidth('16rem')->sortable()->view('title'),
            $this->cell('role')->title(t('Role'))->fixedWidth('6rem')->view('raw'),
            $this->cell('visit')->title(t('Last visit'))->fixedWidth('6rem')->view('raw'),
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
