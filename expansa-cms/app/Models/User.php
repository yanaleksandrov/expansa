<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use Exception;
use App\User\Roles;
use Expansa\Database\Model;
use Expansa\Debug\Error;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
use Expansa\Facades\Validator;
use Expansa\Support\Hash;
use Expansa\Support\Is;

/**
 * Class User represents a user in the system. Handles authentication,
 * profile information, verification, and activity tracking.
 *
 * @property int           $id                         Unique identifier of the user.
 * @property string        $uuid                       Universally unique identifier.
 * @property string        $login                      User login (unique).
 * @property string        $password                   Hashed user password.
 * @property string        $nicename                   Short display name.
 * @property string        $firstname                  User first name.
 * @property string        $lastname                   User last name.
 * @property string        $showname                   Full display name.
 * @property string        $email                      User email address (unique).
 * @property string|null   $locale                     User locale/language.
 * @property string        $status                     User status ('active' or 'inactive').
 * @property bool          $isVerified                 Whether the user has verified their email.
 * @property string|null   $verificationToken          Token used for email verification.
 * @property DateTime|null $verificationTokenExpiresAt Expiration datetime of verification token.
 * @property string|null   $passwordResetToken         Token used for password reset.
 * @property DateTime|null $passwordResetExpiresAt     Expiration datetime of password reset token.
 * @property string        $createdAt                  The date and time when the user was created.
 * @property string        $updatedAt                  The date and time when the user was last updated.
 * @property Field         $field                      A dynamic meta field instance associated with the API key.
 */
class User extends Model
{
    use Model\HasSanitizing;
    use Model\HasValidation;
    use Model\HasTimestamps;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table = 'users';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'login',
        'password',
        'nicename',
        'firstname',
        'lastname',
        'showname',
        'email',
        'locale',
        'status',
        'is_verified',
        'verification_token',
        'verification_token_expires_at',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    /**
     * A list of attributes that can only be created once, and then left unchanged.
     *
     * @var array<string>
     */
    protected array $readonly = [
        'login',
    ];

    /**
     * Session key
     *
     * @var string
     */
    private static string $session_id = EX_DB_PREFIX . 'user_logged';

    /**
     * Current user data.
     */
    private static self $current;

    /**
     * Array of rules for sanitize properties.
     *
     * @return array<string, string>
     */
    protected function getSanitizerRules(): array
    {
        return [
            'login'       => 'login',
            'password'    => 'trim',
            'nicename'    => 'slug:$login',
            'firstname'   => 'ucfirst',
            'lastname'    => 'ucfirst',
            'showname'    => 'ucfirst:$login',
            'email'       => 'email',
            'locale'      => 'locale',
            'status'      => 'trim',
            'is_verified' => 'bool',
        ];
    }

    /**
     * An array of rules for validation when creating and updating a model.
     *
     * @return array<string, string>
     */
    protected function validatorRules(): array
    {
        return [
            'login'    => 'lengthMin:3|lengthMax:60',
            'password' => 'required',
            'email'    => 'email|unique',
        ];
    }

    /**
     * Extend with custom validation rules.
     *
     * @return void
     */
    protected function validatorExtend(): void
    {
        $this->validator->extend(
            'email:unique',
            t('Sorry, that user email address or login is already used!'),
            fn() => ! $this->exists(
                [
                    'login' => $this->login,
                    'email' => $this->email,
                ]
            )
        );
    }

    protected function password(): Model\Attribute
    {
        return Model\Attribute::make(
            set: fn($value) => password_hash($value ?: Hash::generate(), PASSWORD_DEFAULT)
        );
    }

    protected function nicename(): Model\Attribute
    {
        return Model\Attribute::make(
            set: fn($value) => (new self())->generateUniqueNicename($value)
        );
    }

    protected function field(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn($value) => $value instanceof Field ? $value : new Field($this)
        );
    }

    /**
     * Retrieves user info by a given field.
     *
     * @param string|int $value A value for $by field. A user ID, UUID, slug, email address, or login name.
     * @param string     $by    The field to retrieve the user with. ID | login | email | nicename.
     * @return User|Error
     */
    public static function get(string|int $value, string $by = 'id'): User|Error
    {
        try {
            if (empty($value)) {
                throw new Exception(t('You are trying to find a user with an empty :getByField.', $by));
            }

            $by = mb_strtolower($by);
            if (! in_array($by, [ 'id', 'uuid', 'login', 'email', 'nicename' ], true)) {
                throw new Exception(t('Use an ID, UUID, login, email, or nicename to get a user.'));
            }

            $user = parent::get($value, $by);
            if ($user instanceof User) {
                return $user;
            }

            throw new Exception(t('User not found.'));
        } catch (Exception $e) {
            return error('user-get', $e->getMessage());
        }
    }

    /**
     * Update a user in the database. If no ID is found in the received array,
     * the function passes the work to the add method.
     *
     * @param array         $userdata
     * @return User|Error
     */
    public static function update(array $userdata): User|Error
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
            $userdata = Safe::data($userdata, [
                'password'   => 'trim',
                'nicename'   => 'trim',
                'firstname'  => 'tags',
                'lastname'   => 'tags',
                'showname'   => 'tags',
                'email'      => 'email',
                'locale'     => 'locale',
                'registered' => 'datetime',
                'visited'    => 'datetime',
            ])->apply();

            Db::update((new self())->table, array_filter($userdata));

            return self::get($userID);
        }

        return error('user-update', t('User not found.'));
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
            return error('user-delete', t('The user you are trying to delete does not exist.'));
        }

        if ($reassign) {
            // TODO: add functionality for reassign
        }
        return Db::delete((new self())->table, $fields)->rowCount();
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

        if (!session()->isStarted()) {
            session()->start();
        }

        $userID = session()->get(self::$session_id);
        if ($userID) {
            self::$current = self::get($userID);
        }

        if ($callback) {
            $callback(new Field(self::$current));
        }

        return self::$current;
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
        if ($user->id === $userID) {
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
     * @return bool           The user has a role.
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
    public static function isLogged(): bool
    {
        return (bool) abs((int) session()->get(self::$session_id));
    }

    /**
     * Authorizes the user by password and login/email.
     *
     * @param array $data
     * @return User|Error
     */
    public static function login(array $data): User|Error
    {
        $data = Safe::data($data, [
            'login'    => 'login',
            'password' => 'trim',
            'remember' => 'bool',
        ])->apply();

        $userdata = Validator::data($data, [
            'login'    => 'lengthMin:3|lengthMax:60',
            'password' => 'required',
        ])->apply();

        if (!$userdata->isValid()) {
            return error('user-login', $userdata->getErrors());
        }

        [ $loginOrEmail, $password, $remember ] = array_values($data);

        $field = Is::email($loginOrEmail) ? 'email' : 'login';
        $user  = User::get($loginOrEmail, $field);
        if ($user instanceof User) {
            if (password_verify($password, $user->password)) {
                if (!session()->isStarted()) {
                    session()->start();
                }
                session()->set(self::$session_id, $user->id);

                return self::$current = $user;
            }

            return error('user-login', t('User password is incorrect.'));
        }

        return error('user-login', t('User not found: invalid login or email.'));
    }

    /**
     * Де-авторизует текущего пользователя.
     */
    public static function logout(): void
    {
        if (!session()->isStarted()) {
            session()->start();
        }

        self::$current = [];

        $userID = abs((int) session()->get(self::$session_id));
        if ($userID) {
            self::update(
                [
                    'id' => $userID,
                ]
            );
        }
        session()->set(self::$session_id, null);
    }

    /**
     * Generate a unique nicename by appending a numeric suffix if needed.
     *
     * This method checks the database for existing entries with the same nicename
     * and increments the suffix until a unique value is found.
     *
     * @param string $value The base value to generate a unique nicename from.
     * @return string A unique nicename with a numeric suffix if necessary.
     */
    private function generateUniqueNicename(string $value): string
    {
        $suffix = 1;

        while (Db::select((new self())->table, 'id', [ 'nicename' => $value . ( $suffix > 1 ? "-$suffix" : '' ) ])) {
            $suffix++;
        }

        return sprintf('%s%s', $value, $suffix > 1 ? "-$suffix" : '');
    }
}
