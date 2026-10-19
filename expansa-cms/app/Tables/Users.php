<?php

declare(strict_types=1);

namespace App\Tables;

use App\Api\User\Admin;
use App\Api\User\Confirmation;
use App\Models\User;
use App\Support\Dates;
use Expansa\Builders\Table;
use Expansa\Facades\Access;
use Expansa\Facades\Db;
use Expansa\Facades\Role;
use PDO;

final class Users extends Table
{
    public function __construct(

        /**
         * Status the users are filtered by, empty for all.
         */
        public private(set) string $status = '' {
            set => isset(self::statuses()[$value]) ? $value : '';
        },

        /**
         * Text searched in the login, the shown name and the email.
         */
        public readonly string $search = '',
    ) {
        parent::__construct();
    }

    /**
     * Users by the filter with the time of their last sign-in, the first 500 by ID.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $visits = Db::query('SELECT user_id, MAX(created_at) FROM <users_events> WHERE event = \'sign_in\' GROUP BY user_id')
            ?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];

        $columns = ['id [Int]', 'login', 'showname', 'email', 'status', 'roles'];
        $where   = $this->status !== '' ? ['status' => $this->status] : [];
        if ($this->search !== '') {
            $where['OR'] = [
                'login[~]'    => $this->search,
                'showname[~]' => $this->search,
                'email[~]'    => $this->search,
            ];
        }

        $users = Db::select('users', $columns, [
            ...$where,
            'ORDER' => ['id' => 'ASC'],
            'LIMIT' => 500,
        ]) ?? [];

        return array_map(fn (array $user) => [
            'id'     => $user['id'],
            'name'   => $user['showname'] ?: $user['login'],
            'login'  => $user['login'],
            'email'  => $user['email'],
            'status' => self::statuses()[$user['status']] ?? $user['status'],
            'role'   => ((array) json_decode((string) $user['roles'], true))[0] ?? '',
            'visit'  => isset($visits[$user['id']]) ? Dates::format((string) $visits[$user['id']]) : t('Never'),
        ], $users);
    }

    public function cells(): array
    {
        return [
            $this->cell('id')
                ->title('<input type="checkbox" u-bind="trigger">')
                ->fixedWidth('1.125rem')
                ->view('cb'),
            $this->cell('image')
                ->fixedWidth('2rem')
                ->view('image'),
            $this->cell('name')
                ->title(t('Name', context: 'person'))
                ->flexibleWidth('16rem')
                ->sortable()
                ->view('user'),
            $this->cell('status')
                ->title(t('Status'))
                ->fixedWidth('7rem')
                ->view('raw'),
            $this->cell('visit')
                ->title(t('Last Visit'))
                ->fixedWidth('7rem')
                ->view('raw'),
            $this->cell('role')
                ->title(t('Role'))
                ->fixedWidth('7rem')
                ->view('role'),
        ];
    }

    public function headData(): array
    {
        $canCreate = Access::allows(User::current(), 'users_edit');
        $counts    = Db::query('SELECT status, COUNT(*) FROM <users> GROUP BY status')
            ?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];
        $parts     = [
            '' => [
                'label' => t('All') . ' (' . array_sum($counts) . ')',
                'query' => [],
            ],
        ];

        foreach (self::statuses() as $key => $label) {
            $parts[$key] = [
                'label' => $label . ' (' . (int) ($counts[$key] ?? 0) . ')',
                'query' => ['status' => $key],
            ];
        }

        return [
            'title'  => t('Users'),
            'parts'  => $parts,
            'part'   => $this->status,
            'find'   => t('Search by name, login or email'),
            'create' => $canCreate ? t('Add New User') : '',
            'link'   => $canCreate ? url('dashboard/profile?new') : '',
        ];
    }

    /**
     * Turn the accounts on or off, sign them out, send a password reset link or give a role, for those
     * who manage the users; the current password is asked unless the session is confirmed.
     *
     * @return array<string, mixed>
     */
    public function bulk(): array
    {
        $admin = User::current();
        if (! Access::allows($admin, 'users_edit')) {
            return [];
        }

        $roles = [];
        foreach (Admin::assignableRoles($admin) as $role => $name) {
            $roles["role:$role"] = $name;
        }

        return [
            'endpoint' => 'user/bulk',
            'actions'  => [
                'activate'       => t('Turn On'),
                'disable'        => t('Disable'),
                'sign-out'       => t('Sign Out Everywhere'),
                'password-reset' => t('Send Password Reset'),
                'role'           => [
                    'label'   => t('Change Role To'),
                    'options' => $roles,
                ],
            ],
            'confirm'  => ['disable', 'sign-out'],
            'password' => Confirmation::isConfirmed($admin) ? [] : ['activate', 'disable', 'sign-out'],
        ];
    }

    public function filters(): array
    {
        return [
            'role' => [
                'label'   => t('Role'),
                'icon'    => 'ph ph-person-simple-run',
                'options' => array_map(static fn (array $role) => $role['name'], Role::all()),
            ],
        ];
    }

    /**
     * Get the labels of the user statuses by value.
     *
     * @return array<string, string>
     */
    private static function statuses(): array
    {
        return [
            User::STATUS_ACTIVE   => t('Active', context: 'user'),
            User::STATUS_INACTIVE => t('Disabled', context: 'user'),
        ];
    }

    public function notFoundData(): array
    {
        if ($this->status !== '' || $this->search !== '') {
            return [
                'title'       => t('No users found'),
                'description' => t('Nothing matches the filter.'),
                'buttons'     => [
                    [
                        'label' => t('Show All Users'),
                        'url'   => url('dashboard/edit?table=users'),
                    ],
                ],
            ];
        }

        $create = Access::allows(User::current(), 'users_edit')
            ? [
                [
                    'label' => t('Add New User'),
                    'url'   => url('/dashboard/profile?new'),
                ],
            ]
            : [];

        return [
            'title'       => t('No users found'),
            'description' => t("You don't have any users yet. Add them manually or import them from a CSV file."),
            'buttons'     => [
                ...$create,
                [
                    'label' => t('Import'),
                    'url'   => url('/dashboard/import'),
                ],
            ],
        ];
    }
}
