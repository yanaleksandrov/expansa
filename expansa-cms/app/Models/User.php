<?php

declare(strict_types=1);

namespace App\Models;

use App\Api\User\Events;
use App\Api\User\Sessions;
use App\Post\Type;
use App\Support\Passwords;
use DateTime;
use Expansa\Access\Contracts\Subject;
use Expansa\Auth\Contracts\Identity;
use Expansa\Database\Attribute;
use Expansa\Database\Contracts\Fieldable;
use Expansa\Database\FieldEav;
use Expansa\Database\Model;
use Expansa\Database\Traits\HasFieldEav;
use Expansa\Database\Traits\HasHiddenAttributes;
use Expansa\Database\Traits\HasReadonlyAttributes;
use Expansa\Database\Traits\HasSanitizing;
use Expansa\Database\Traits\HasSoftDeletes;
use Expansa\Database\Traits\HasTimestamps;
use Expansa\Database\Traits\HasUuid;
use Expansa\Database\Traits\HasValidation;
use Expansa\Facades\Access;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Role;
use Expansa\Support\Error;
use Expansa\Support\Hash;

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
 * @property string        $status                     User status (see User::STATUS_* constants).
 * @property bool          $isVerified                 Whether the user has verified their email.
 * @property string|null   $verificationToken          Token used for email verification.
 * @property DateTime|null $verificationTokenExpiresAt Expiration datetime of verification token.
 * @property string|null   $passwordResetToken         Token used for password reset.
 * @property DateTime|null $passwordResetExpiresAt     Expiration datetime of password reset token.
 * @property DateTime      $createdAt                  The date and time when the user was created.
 * @property DateTime      $updatedAt                  The date and time when the user was last updated.
 * @property DateTime|null $deletedAt                  The date and time when the user was soft-deleted, if at all.
 * @property FieldEav      $field                      A dynamic meta field instance associated with the user.
 * @property array         $roles                      User roles list.
 */
class User extends Model implements Fieldable, Identity, Subject
{
    use HasSanitizing;
    use HasValidation;
    use HasTimestamps;
    use HasReadonlyAttributes;
    use HasHiddenAttributes;
    use HasSoftDeletes;
    use HasFieldEav;
    use HasUuid;

    /**
     * The "active" value of the `status` column.
     */
    public const string STATUS_ACTIVE = 'active';

    /**
     * The "inactive" value of the `status` column.
     */
    public const string STATUS_INACTIVE = 'inactive';

    /**
     * Role assigned to a new user when none is given and no `users.role`
     * option is configured.
     */
    private const string DEFAULT_ROLE = 'subscriber';

    /**
     * Role names assigned to the user, the `roles` column through roles(); Access permissions come from them.
     *
     * @var string[]
     */
    public array $roles {
        get => $this->getAttribute('roles');
        // a block: the short form would store setAttribute()'s return value, the model, in the property
        set {
            $this->setAttribute('roles', $value);
        }
    }

    /**
     * Login: Auth finds the user of a token by it.
     */
    public string $identifier {
        get => $this->login;
    }

    /**
     * Password hash: Auth tokens are signed with it, a new password signs out every device.
     */
    public string $stamp {
        get => $this->password;
    }

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    public protected(set) string $table = 'users';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    public protected(set) array $fillable = [
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
        'id',
        'uuid',
        'login',
    ];

    /**
     * A list of attributes excluded from the model's array/JSON representation.
     *
     * @var array<string>
     */
    protected array $hidden = [
        'password',
        'verification_token',
        'password_reset_token',
    ];

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
            'status'   => 'in:' . self::STATUS_ACTIVE . ',' . self::STATUS_INACTIVE,
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
            t('Sorry, that email address or login is already in use.'),
            fn() => ! $this->exists(
                [
                    'login' => $this->login,
                    'email' => $this->email,
                ]
            )
        );

        $this->validator->extend(
            'status:in',
            t('Status must be either :active or :inactive.', self::STATUS_ACTIVE, self::STATUS_INACTIVE)
        );
    }

    /**
     * Hashes the password before it's stored. Falls back to a random secret
     * (see Hash::generate()) if an empty value is given, rather than storing
     * the hash of an empty string.
     */
    protected function password(): Attribute
    {
        return new Attribute(
            set: fn($value) => password_hash($value ?: Hash::generate(), PASSWORD_DEFAULT)
        );
    }

    /**
     * Derives a unique, URL-safe nicename from whatever value is set,
     * appending a numeric suffix if it would otherwise collide.
     */
    protected function nicename(): Attribute
    {
        return new Attribute(
            set: fn($value) => $this->generateUniqueNicename($value)
        );
    }

    /**
     * Guarantees a real bool on read regardless of how the value got here — the
     * sanitizer's 'is_verified' => 'bool' rule only runs on fill()/setAttribute(),
     * not on hydration from the database, so a freshly-fetched user's raw value is
     * whatever the driver returns for TINYINT(1) (an int), not a PHP bool.
     */
    protected function isVerified(): Attribute
    {
        return new Attribute(
            get: fn($value) => (bool) $value
        );
    }

    /**
     * Role names assigned to the user, stored as a JSON array in its own
     * `roles` column — not a Field/meta entry, and not mass-assignable (see
     * assignRole()/removeRole(): granting a role has security implications
     * that a public fillable attribute shouldn't be exposed to).
     */
    protected function roles(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value ? json_decode($value, true) : [],
            set: fn($value) => json_encode(array_values(array_unique((array) $value)))
        );
    }

    /**
     * Exposes the raw stored timestamp as a DateTime instead of a string.
     */
    protected function verificationTokenExpiresAt(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value ? new DateTime($value) : null
        );
    }

    /**
     * Exposes the raw stored timestamp as a DateTime instead of a string.
     */
    protected function passwordResetExpiresAt(): Attribute
    {
        return new Attribute(
            get: fn($value) => $value ? new DateTime($value) : null
        );
    }

    /**
     * Retrieves user info by a given field.
     *
     * @param string|int $value A value for $by field. A user ID, UUID, slug, email address, or login name.
     * @param string     $by    The field to retrieve the user with. ID | login | email | nicename.
     * @return User|Error
     */
    public static function find(string|int $value, string $by = 'id'): User|Error
    {
        if (empty($value)) {
            return error('user-find', t('You are trying to find a user with an empty :getByField.', $by));
        }

        $by = mb_strtolower($by);
        if (! in_array($by, [ 'id', 'uuid', 'login', 'email', 'nicename' ], true)) {
            return error('user-find', t('Use an ID, UUID, login, email, or nicename to get a user.'));
        }

        $user = parent::get($value, $by);

        return $user instanceof User ? $user : error('user-find', t('User not found'));
    }

    /**
     * Create a new user in the database.
     *
     * @param array $userdata
     * @return User|Error
     */
    public static function create(array $userdata): User|Error
    {
        // Only takes effect if the key is entirely absent — fill() sanitizes/mutates only keys $userdata actually has.
        $userdata += ['status' => self::STATUS_ACTIVE, 'nicename' => ''];

        $user = new self($userdata);

        if (! $user->isValid()) {
            return error('user-add', $user->getValidatorErrors());
        }

        // Not mass-assignable (see roles()) — read the raw input so a caller can still request a role, falling back to the default.
        $role = $userdata['role'] ?? Option::get('users.role', self::DEFAULT_ROLE);
        if (Role::hasRole($role)) {
            $user->roles = [$role];
        }

        if (! $user->save() instanceof self) {
            return error('user-add', t('Failed to save the user to the database.'));
        }

        return $user;
    }

    /**
     * Update this user in the database with the given attributes.
     *
     * @param array $userdata
     * @return User|Error
     */
    public function update(array $userdata): User|Error
    {
        // 'id' isn't fillable and 'login' is readonly, so fill() already ignores both.
        $this->fill($userdata);

        if (! $this->save() instanceof self) {
            return error('user-update', t('Failed to save the user to the database.'));
        }

        return $this;
    }

    /**
     * Reassign all posts, across every registered post type, from this user
     * to another. Useful to call before deleting a user, since deletion
     * itself (via {@see \Expansa\Database\Query::delete()}) does not touch
     * content ownership.
     *
     * @param int $newUserId ID of the user to become the new author.
     * @return int           Number of post types whose rows were reassigned.
     */
    public function reassign(int $newUserId): int
    {
        $reassigned = 0;

        foreach (Type::fetch() as $type) {
            $result = Db::update($type->table, ['author_id' => $newUserId], ['author_id' => $this->id]);

            $reassigned += $result && $result->rowCount() ? 1 : 0;
        }

        return $reassigned;
    }

    /**
     * Get the signed-in user of Auth::user() as a User.
     *
     * @return User|null Null for a guest.
     */
    public static function current(): ?User
    {
        $user = Auth::user();

        return $user instanceof self ? $user : null;
    }

    /**
     * Get the users as select options by id: the shown name or the login, by name; the first 500,
     * the same limit as the users table.
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        $users = Db::select('users', ['id [Int]', 'login', 'showname'], ['ORDER' => ['showname' => 'ASC', 'login' => 'ASC'], 'LIMIT' => 500]) ?? [];

        return array_column(array_map(fn (array $user) => ['id' => $user['id'], 'name' => $user['showname'] ?: $user['login']], $users), 'name', 'id');
    }

    /**
     * Returns whether this user has the specified capability, through Access permissions.
     *
     * @param string $capability Capability name.
     * @return bool              Whether the user has the given capability.
     */
    public function can(string $capability): bool
    {
        return Access::allows($this, $capability);
    }

    /**
     * Checks whether this user has the specified role.
     *
     * @param string $role Role name.
     * @return bool        The user has a role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * Assign a registered role to this user. Does nothing (returns true) if
     * the user already has that role.
     *
     * @param string $role Role name, as added with Role::add().
     * @return Error|bool  True once assigned.
     */
    public function assignRole(string $role): Error|bool
    {
        if (! Role::hasRole($role)) {
            return error('user-assign-role', t('That role is not registered.'));
        }

        if (in_array($role, $this->roles, true)) {
            return true;
        }

        $this->roles = [...$this->roles, $role];

        return $this->save() instanceof self;
    }

    /**
     * Remove a role from this user.
     *
     * @param string $role Role name to remove.
     * @return Error|bool
     */
    public function removeRole(string $role): Error|bool
    {
        $this->roles = array_diff($this->roles, [$role]);

        return $this->save() instanceof self;
    }

    /**
     * Set a new password. Auth tokens are signed with the password hash, so every device is signed out;
     * for the current user the token is re-signed with the same lifetime and this session goes on.
     *
     * @param string $password New password, see Passwords::check().
     * @return User|Error
     */
    public function changePassword(string $password): User|Error
    {
        $refusal = Passwords::check($password, [$this->login, $this->email]);
        if ($refusal !== null) {
            return error('user-password', $refusal);
        }

        // resolved before the update: the token of the request is signed with the old hash
        $isCurrent = Auth::user()?->identifier === $this->login;

        $updated = $this->update([
            'password'                  => $password,
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
        ]);

        if ($updated instanceof self) {
            if ($isCurrent) {
                Auth::refresh($this);
            }

            // tokens of other devices stopped working with the old hash, their rows go too
            Sessions::deleteOthers($this);
            Events::record($this, 'password_changed');
        }

        return $updated;
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

        while ($this->exists([ 'nicename' => $value . ( $suffix > 1 ? "-$suffix" : '' ) ])) {
            $suffix++;
        }

        return sprintf('%s%s', $value, $suffix > 1 ? "-$suffix" : '');
    }
}
