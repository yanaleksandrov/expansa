<?php

declare(strict_types=1);

namespace App\Tables;

use App\Api\User\Admin;
use App\Api\User\Confirmation;
use App\Models\User;
use App\Support\Dates;
use Expansa\Builders\Table;
use Expansa\Builders\Table\Cell;
use Expansa\Builders\Table\Queried;
use Expansa\Database\Query\Raw;
use Expansa\Facades\Access;
use Expansa\Facades\Db;
use Expansa\Facades\Role;
use PDO;

/**
 * Users with the time of their last sign-in: filtered by the status, the role and the search,
 * sorted and read by pages in the database.
 *
 * @package App\Tables
 */
final class Users extends Table implements Queried
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
     * Users of the current page with the time of their last sign-in.
     *
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $columns = ['id [Int]', 'login', 'showname', 'email', 'status', 'roles'];
        $users   = Db::select('users', $columns, $this->paged($this->where(), ['id' => 'ASC'])) ?? [];

        // the last sign-in of the users of the page only
        $visits = [];
        $ids    = array_column($users, 'id');
        if ($ids !== []) {
            $visits = Db::query(
                'SELECT user_id, MAX(created_at) FROM <users_events> WHERE event = \'sign_in\' AND user_id IN (' . implode(',', array_map(intval(...), $ids)) . ') GROUP BY user_id'
            )?->fetchAll(PDO::FETCH_KEY_PAIR) ?? [];
        }

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

    public function count(): int
    {
        return (int) Db::count('users', $this->filtered($this->where()));
    }

    /**
     * The name shown in the list is the shown name or, without one, the login, so the users are sorted by it.
     *
     * @param Cell                  $cell
     * @param string                $direction
     * @param array<string, string> $order
     * @return array<string, string>|Raw
     */
    protected function orderBy(Cell $cell, string $direction, array $order): array|Raw
    {
        if ($cell->key !== 'name') {
            return parent::orderBy($cell, $direction, $order);
        }

        return Db::raw("COALESCE(NULLIF(<showname>, ''), <login>) $direction, <id> ASC");
    }

    /**
     * A role is a quoted name in the JSON list of the `roles` column: "is" any of the roles, "is not" none of them.
     *
     * @param string   $field
     * @param string   $operator
     * @param string[] $values
     * @return array<string, mixed>
     */
    protected function filterWhere(string $field, string $operator, array $values): array
    {
        if ($field !== 'role') {
            return parent::filterWhere($field, $operator, $values);
        }

        // the percents are set here: an underscore of a name would stop the LIKE from adding them
        $roles = array_map(fn (string $role) => '%"' . $role . '"%', $values);

        return $operator === 'not' ? ['roles[!~]' => ['AND' => $roles]] : ['roles[~]' => $roles];
    }

    /**
     * Conditions of the list: the status and the search in the login, the shown name and the email.
     *
     * @return array<string, mixed>
     */
    private function where(): array
    {
        $where = $this->status !== '' ? ['status' => $this->status] : [];
        if ($this->search !== '') {
            $where['OR'] = [
                'login[~]'    => $this->search,
                'showname[~]' => $this->search,
                'email[~]'    => $this->search,
            ];
        }

        return $where;
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
