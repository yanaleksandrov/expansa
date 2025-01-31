<?php

declare(strict_types=1);

namespace App;

use Exception;
use App\User\Roles;
use App\User\Traits;
use Expansa\Error;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use Expansa\Facades\Validator;
use Expansa\Security\Validator as SecurityValidator;
use Expansa\Session;
use Expansa\Support\Hash;
use Expansa\Support\Is;

/**
 * This class handles user-related operations including user creation, retrieval,
 * updating, deletion, and session management. It extends the Users class to inherit
 * user-related functionalities.
 */
final class User
{
    use Traits;

    public static string $table = 'users';

    public int $id = 0;

    public string $login = '';

    public string $password = '';

    public string $nicename = '';

    public string $firstname = '';

    public string $lastname = '';

    public string $showname = '';

    public string $email = '';

    public ?string $locale = '';

    public string $registered = '';

    public string $visited = '';

    public array $fields;

    public array $roles = [];

    public array $capabilities = [];

    /**
     * Retrieves user info by a given field.
     *
     * @param string|int    $value    A value for $field. A user ID, slug, email address, or login name.
     * @param string        $getBy    The field to retrieve the user with. ID | login | email | nicename.
     * @param callable|null $callback
     * @return User|Error
     */
    public static function get(string|int $value, string $getBy = 'id', ?callable $callback = null): User|Error
    {
        try {
            if (empty($value)) {
                throw new Exception(t('You are trying to find a user with an empty :getByField.', $getBy));
            }

            if (! in_array($getBy, [ 'id', 'login', 'email', 'nicename' ], true)) {
                throw new Exception(t('To get a user, use an ID, login, email or nicename.'));
            }

            $users    = Db::select(self::$table, '*', [ $getBy => $value ], [ 'LIMIT' => 1 ]);
            $userdata = (array) ( $users[0] ?? [] );
            if ($userdata) {
                $user = new self();
                foreach ($userdata as $field => $value) {
                    if (property_exists($user, $field)) {
                        $user->$field = $value;
                    }
                }

                if ($callback) {
                    $callback(new Field($user));
                }

                return $user;
            }

            throw new Exception(t('User not found.'));
        } catch (Exception $e) {
            return new Error('user-get', $e->getMessage());
        }
    }

    /**
     * Insert a user into the database.
     *
     * The showname & nickname fields should not be left empty, because nickname
     * is part of the URL of the user's page, and showname is displayed as the name.
     * Therefore, we generate it based on the login.
     *
     * @param array         $userdata
     * @param callable|null $callback
     *
     * @return User|Error The newly created user's ID or an Error object if the user could not be created.
     */
    public static function add(array $userdata, ?callable $callback = null): User|Error
    {

        $userdata = Safe::data($userdata, [
            'login'    => 'login',
            'password' => 'trim',
            'email'    => 'email',
            'showname' => 'ucfirst:$login',
            'nicename' => 'slug:$login|unique',
        ])->extend('unique', function ($value) {
            $suffix = 1;
            while (Db::select(self::$table, 'id', [ 'nicename' => $value . ( $suffix > 1 ? "-$suffix" : '' ) ])) {
                $suffix++;
            }
            return sprintf('%s%s', $value, $suffix > 1 ? "-$suffix" : '');
        })->apply();

        // validate incoming user data
        $userdata = Validator::data(
            $userdata,
            [
                'login'    => 'lengthMin:3|lengthMax:60',
                'password' => 'required',
                'email'    => 'email|unique',
            ]
        )->extend(
            'email:unique',
            t('Sorry, that email address or login is already used!'),
            fn($validator) => ! self::exists(
                [
                    'login' => $validator->fields['login'],
                    'email' => $validator->fields['email'],
                ]
            )
        )->apply();

        if ($userdata instanceof SecurityValidator) {
            return new Error('user-add', $userdata->errors);
        }

        [ $login, $password ] = array_values($userdata);
        $userdata['password'] = $password ? password_hash($password, PASSWORD_DEFAULT) : Hash::generate();

        $user_count = Db::insert(self::$table, $userdata)->rowCount();
        if ($user_count !== 1) {
            return new Error('user-add', t('Something went wrong, it was not possible to add a user.'));
        }

        $user = self::get($login, 'login');

        if ($callback) {
            $callback(new Field($user));
        }

        return $user;
    }

    /**
     * Update a user in the database. If no ID is found in the received array,
     * the function passes the work to the add method.
     *
     * @param array $userdata
     * @param callable|null $callback
     * @return User|Error
     */
    public static function update(array $userdata, ?callable $callback = null): User|Error
    {
        $userID = Safe::absint($userdata['id'] ?? 0);
        if (! $userID) {
            return self::add($userdata);
        }

        // remove unchanged parameters of the user
        unset($userdata['id']);
        unset($userdata['login']);

        $user = self::get($userID);
        if ($user instanceof User) {
            $userdata = Safe::data(
                $userdata,
                [
                    'password'   => 'trim',
                    'nicename'   => 'trim',
                    'firstname'  => 'tags',
                    'lastname'   => 'tags',
                    'showname'   => 'tags',
                    'email'      => 'email',
                    'locale'     => 'locale',
                    'registered' => 'datetime',
                    'visited'    => 'datetime',
                ]
            )->apply();

            $userdata = array_filter($userdata);
            if (Db::update(self::$table, $userdata)->rowCount()) {
                if ($callback) {
                    $callback(new Field($user));
                }

                return self::get($userID);
            }
        }

        return new Error('user-update', t('User not found.'));
    }

    /**
     * Remove user and optionally reassign posts and links to another user.
     *
     * If the $reassign parameter is not assigned to a User ID, then all posts will
     * be deleted of that user. The action {@see 'delete_user'} that is passed the User ID
     * being deleted will be run after the posts are either reassigned or deleted.
     * The user meta will also be deleted that are for that User ID.
     *
     * @param  int   $userID   User ID.
     * @param  int   $reassign Optional. Reassign posts to new User ID.
     * @return Error|int       The number of remote users or false.
     */
    public static function delete(int $userID, int $reassign = 0): Error|int
    {
        $fields = [
            'id' => abs($userID),
        ];

        if (! self::exists($fields)) {
            return new Error('user-delete', t('The user you are trying to delete does not exist.'));
        }

        if ($reassign) {
        }
        return Db::delete(self::$table, $fields)->rowCount();
    }

    /**
     * Получает данные текущего, зарегистрированного пользователя.
     *
     * @param callable|null $callback
     * @return User|Error|null
     */
    public static function current(?callable $callback = null): User|Error|null
    {
        if (self::$current) {
            return self::$current;
        }

        Session::start();

        $userID = Session::get(self::$session_id);
        if ($userID) {
            self::$current = self::get($userID);
        }

        if ($callback) {
            $callback(new Field(self::$current));
        }

        return self::$current;
    }

    /**
     * Searches for users by the specified parameters
     *
     * @param array $fields
     * @return bool Array of fields and values to search for users.
     */
    public static function exists(array $fields): bool
    {
        $users = Db::select(self::$table, '*', [ 'OR' => $fields ]);
        if ($users) {
            return true;
        }
        return false;
    }

    /**
     * Returns whether a particular user has the specified capability.
     *
     * @param integer $userID User ID.
     * @param string $capabilities Capability name.
     * @return   bool              Whether the user has the given capability.
     */
    public static function can(int $userID, string $capabilities): bool
    {
        $roles = [];
        $user  = self::current();
        if ((int) $user->id === $userID) {
            $roles = $user->roles ?? [];
        } else {
            $user = self::get($userID);
            if ($user) {
                $roles = $user->roles ?? [];
            }
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                return Roles::hasCap($role, $capabilities);
            }
        }
        return false;
    }

    /**
     * Checks whether the user is with the specified role.
     *
     * @param integer $userID User ID.
     * @param string $role    Role name.
     * @return   bool         The user has a role.
     */
    public static function is(int $userID, string $role): bool
    {
        $roles = [];
        $user  = self::current();
        if ($user->id === $userID) {
            $roles = $user->roles ?? [];
        } else {
            $user = self::get($userID);
            if ($user) {
                $roles = $user->roles ?? [];
            }
        }
        return in_array($role, $roles, true);
    }

    /**
     * Проверяет, авторизован ли пользователь в этом сеансе.
     *
     * @return   bool
     */
    public static function logged(): bool
    {
        Session::start();
        $userID = abs((int) Session::get(self::$session_id));
        if ($userID) {
            return true;
        }
        return false;
    }

    /**
     * Authorizes the user by password and login/email.
     *
     * @param array $userdata
     * @return User|Error
     */
    public static function login(array $userdata): User|Error
    {
        $userdata = Safe::data($userdata, [
            'login'    => 'login',
            'password' => 'trim',
            'remember' => 'bool',
        ])->apply();

        $userdata = Validator::data($userdata, [
            'login'    => 'lengthMin:3|lengthMax:60',
            'password' => 'required',
        ])->apply();

        if ($userdata instanceof Validator) {
            return new Error('user-login', $userdata);
        }

        [ $loginOrEmail, $password, $remember ] = array_values($userdata);

        $field = Is::email($loginOrEmail) ? 'email' : 'login';
        $user  = User::get($loginOrEmail, $field);
        if ($user instanceof User) {
            if (password_verify($password, $user->password)) {
                if ($remember) {
                    Session::start();
                } else {
                    Session::start(1);
                }
                Session::set(self::$session_id, $user->id);

                return self::$current = $user;
            }

            return new Error('user-login', t('User password is incorrect.'));
        }

        return new Error('user-login', t('User not found: invalid login or email.'));
    }

    /**
     * Де-авторизует текущего пользователя.
     */
    public static function logout(): void
    {
        Session::start();

        self::$current = [];

        $userID = abs((int) Session::get(self::$session_id));
        if ($userID) {
            self::update(
                [
                    'id' => $userID,
                ]
            );
        }
        Session::set(self::$session_id, null);
    }
}
