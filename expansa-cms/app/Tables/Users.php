<?php

declare(strict_types=1);

namespace App\Tables;

use App\Models\User;
use Expansa\Builders\Table;
use Expansa\Facades\Db;
use PDO;

final class Users extends Table
{
    /**
     * Users with the time of their last sign-in, the first 500 by ID.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $visits = Db::query('SELECT user_id, MAX(created_at) FROM <user_events> WHERE event = \'sign_in\' GROUP BY user_id')
            ?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

        $columns = ['id [Int]', 'login', 'showname', 'email', 'status', 'roles'];
        $users   = Db::select('users', $columns, ['ORDER' => ['id' => 'ASC'], 'LIMIT' => 500]) ?? [];

        return array_map(fn (array $user) => [
            'id'     => $user['id'],
            'name'   => $user['showname'] ?: $user['login'],
            'login'  => $user['login'],
            'email'  => $user['email'],
            'status' => $user['status'] === User::STATUS_ACTIVE ? t('Active') : t('Disabled'),
            'role'   => ((array) json_decode((string) $user['roles'], true))[0] ?? '',
            'visit'  => isset($visits[$user['id']]) ? date('Y-m-d H:i', (int) strtotime($visits[$user['id']])) : t('Never'),
        ], $users);
    }

    public function cells(): array
    {
        return [
            $this->cell('id')->title('<input type="checkbox" u-bind="trigger" />')->fixedWidth('1rem')->view('cb'),
            $this->cell('name')->title(t('Name'))->flexibleWidth('16rem')->sortable()->view('user'),
            $this->cell('status')->title(t('Status'))->fixedWidth('6rem')->view('raw'),
            $this->cell('visit')->title(t('Last Visit'))->fixedWidth('8rem')->view('raw'),
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
            'title'       => t('No users found'),
            'description' => t('You don&apos;t have any users yet. <a @click="$dialog.open(`tmpl-post-editor`, postEditorDialog)">Add them manually</a> or [import via CSV](:importLink).', url('/dashboard/import')),
        ];
    }
}
