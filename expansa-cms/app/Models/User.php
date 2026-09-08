<?php

declare(strict_types=1);

namespace App\Models;

use DateTime;
use App\Post\Type;
use App\User\Roles;
use Exception;
use Expansa\Cookie\Cookie;
use Expansa\Database\Model;
use Expansa\Debug\Error;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;
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
 * @property string        $status                     User status (see User::STATUS_* constants).
 * @property bool          $isVerified                 Whether the user has verified their email.
 * @property string|null   $verificationToken          Token used for email verification.
 * @property DateTime|null $verificationTokenExpiresAt Expiration datetime of verification token.
 * @property string|null   $passwordResetToken         Token used for password reset.
 * @property DateTime|null $passwordResetExpiresAt     Expiration datetime of password reset token.
 * @property DateTime      $createdAt                  The date and time when the user was created.
 * @property DateTime      $updatedAt                  The date and time when the user was last updated.
 * @property DateTime|null $deletedAt                  The date and time when the user was soft-deleted, if at all.
 * @property Field         $field                      A dynamic meta field instance associated with the user.
 * @property array<string> $roles                      Role names assigned to the user (see App\User\Roles).
 */
class User extends Model
{
    use Model\HasSanitizing;
    use Model\HasValidation;
    use Model\HasTimestamps;
    use Model\HasReadonlyAttributes;
    use Model\HasHiddenAttributes;
    use Model\HasSoftDeletes;

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
     * Name of the cookie that carries the signed authentication token.
     *
     * @var string
     */
    private static string $cookieName = EX_DB_PREFIX . 'auth';

    /**
     * Lifetime of the authentication cookie when "remember me" is not checked.
     */
    private const int COOKIE_TTL_SHORT = 172800; // 2 days

    /**
     * Lifetime of the authentication cookie when "remember me" is checked.
     */
    private const int COOKIE_TTL_LONG = 1209600; // 14 days

    /**
     * Current user data, cached for the lifetime of the request.
     */
    private static ?User $current = null;

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
            t('Sorry, that user email address or login is already used!'),
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
    protected function password(): Model\Attribute
    {
        return Model\Attribute::make(
            set: fn($value) => password_hash($value ?: Hash::generate(), PASSWORD_DEFAULT)
        );
    }

    /**
     * Derives a unique, URL-safe nicename from whatever value is set,
     * appending a numeric suffix if it would otherwise collide.
     */
    protected function nicename(): Model\Attribute
    {
        return Model\Attribute::make(
            set: fn($value) => $this->generateUniqueNicename($value)
        );
    }

    /**
     * Guarantees a real bool on read regardless of how the value got here — the
     * sanitizer's 'is_verified' => 'bool' rule only runs on fill()/setAttribute(),
     * not on hydration from the database, so a freshly-fetched user's raw value is
     * whatever the driver returns for TINYINT(1) (an int), not a PHP bool.
     */
    protected function isVerified(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn($value) => (bool) $value
        );
    }

    /**
     * Lazily resolves the dynamic per-user meta storage for arbitrary,
     * non-structural fields (see Field) — not the same thing as roles().
     */
    protected function field(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn($value) => $value instanceof Field ? $value : new Field($this)
        );
    }

    /**
     * Role names assigned to the user, stored as a JSON array in its own
     * `roles` column — not a Field/meta entry, and not mass-assignable (see
     * assignRole()/removeRole(): granting a role has security implications
     * that a public fillable attribute shouldn't be exposed to).
     */
    protected function roles(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn($value) => $value ? json_decode($value, true) : [],
            set: fn($value) => json_encode(array_values(array_unique((array) $value)))
        );
    }

    /**
     * Exposes the raw stored timestamp as a DateTime instead of a string.
     */
    protected function verificationTokenExpiresAt(): Model\Attribute
    {
        return Model\Attribute::make(
            get: fn($value) => $value ? new DateTime($value) : null
        );
    }

    /**
     * Exposes the raw stored timestamp as a DateTime instead of a string.
     */
    protected function passwordResetExpiresAt(): Model\Attribute
    {
        return Model\Attribute::make(
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

        return $user instanceof User ? $user : error('user-find', t('User not found.'));
    }

    /**
     * Create a new user in the database.
     *
     * @param array $userdata
     * @return User|Error
     * @throws Exception
     */
    public static function create(array $userdata): User|Error
    {
        // Both defaults below only take effect if the key is entirely absent:
        // fill() only calls setAttribute() (and so only runs sanitizers/mutators)
        // for keys actually present in $userdata. Without this, a caller that
        // omits 'status' fails the status:in validation rule, and one that
        // omits 'nicename' gets a null nicename instead of one derived from
        // login (see nicename()'s 'slug:$login' sanitizer rule).
        $userdata += ['status' => self::STATUS_ACTIVE, 'nicename' => ''];

        $user = new self()->fill($userdata);

        if (! $user->isValid()) {
            return error('user-add', $user->getValidatorErrors());
        }

        // Not mass-assignable (see roles()) — read directly off the raw input
        // so a caller can still request a specific role, falling back to the
        // configured default for self-registration.
        $role = $userdata['role'] ?? Options::get('users.role', self::DEFAULT_ROLE);
        if (Roles::exists($role)) {
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
     * @throws Exception
     */
    public function update(array $userdata): User|Error
    {
        // 'id' isn't fillable and 'login' is readonly, so fill() already
        // ignores both — no need to strip them from $userdata by hand.
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
     * Retrieves data of the current, logged-in user.
     *
     * Authentication is resolved from a signed cookie rather than a server-side
     * session, so anonymous traffic (e.g. bots) never causes anything to be
     * written on the server.
     *
     * @param callable|null $callback
     * @return User|null
     */
    public static function current(?callable $callback = null): ?User
    {
        if (self::$current !== null) {
            return self::$current;
        }

        $cookie = Cookie::get(self::$cookieName, '');
        if ($cookie) {
            self::$current = self::verifyAuthCookie($cookie);

            if (! self::$current instanceof self) {
                self::clearAuthCookie();
            }
        }

        if ($callback && self::$current instanceof self) {
            $callback(new Field(self::$current));
        }

        return self::$current;
    }

    /**
     * Returns whether this user has the specified capability.
     *
     * @param string $capabilities Capability name.
     * @return bool                Whether the user has the given capability.
     */
    public function can(string $capabilities): bool
    {
        return array_any($this->roles, fn($role) => Roles::hasCap($role, $capabilities));
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
     * @param string $role Role name, as registered via App\User\Roles::register().
     * @return Error|bool  True once assigned.
     */
    public function assignRole(string $role): Error|bool
    {
        if (! Roles::exists($role)) {
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
     * Checks whether the current request carries a valid authentication cookie.
     *
     * @return   bool
     */
    public static function isLogged(): bool
    {
        return self::current() instanceof self;
    }

    /**
     * Authorizes the user by password and login/email.
     *
     * @param array $data
     * @return User|Error
     */
    public static function login(array $data): User|Error
    {
        $loginOrEmail = Safe::login($data['login'] ?? '');
        $password     = Safe::trim($data['password'] ?? '');
        $remember     = Safe::bool($data['remember'] ?? false);

        $field = Is::email($loginOrEmail) ? 'email' : 'login';
        $user  = User::find($loginOrEmail, $field);

        // Same message for "no such account" and "wrong password": telling them
        // apart would let an attacker enumerate which logins/emails are registered.
        if (! $user instanceof User || ! password_verify($password, $user->password)) {
            return error('user-login', t('These credentials do not match our records.'));
        }

        self::setAuthCookie($user, time() + ($remember ? self::COOKIE_TTL_LONG : self::COOKIE_TTL_SHORT), $remember);

        return self::$current = $user;
    }

    /**
     * Logs out the current user by discarding the authentication cookie.
     */
    public static function logout(): void
    {
        self::clearAuthCookie();

        self::$current = null;
    }

    /**
     * Sign an authentication payload for the given user.
     *
     * The user's password hash is folded into the signature, so changing the
     * password (or rotating EX_AUTH_KEY) invalidates every cookie issued before.
     *
     * @param User $user       The user to issue the cookie for.
     * @param int  $expiration Unix timestamp after which the cookie is no longer valid.
     * @return string
     */
    private static function signAuthCookie(User $user, int $expiration): string
    {
        $payload = "$user->login|$expiration";

        return $payload . '|' . hash_hmac('sha256', $payload . '|' . $user->password, EX_AUTH_KEY);
    }

    /**
     * Verify a signed authentication cookie and resolve it to a user.
     *
     * @param string $cookie Raw cookie value.
     * @return User|null The user if the cookie is valid and not expired, null otherwise.
     */
    private static function verifyAuthCookie(string $cookie): ?User
    {
        // login is matched greedily, so a "|" inside it (however unlikely) can't
        // desynchronize the trailing timestamp/hmac, which always have a fixed shape.
        if (! preg_match('/^(.+)\|(\d+)\|([a-f0-9]{64})$/', $cookie, $matches)) {
            return null;
        }

        [ , $login, $expiration, $hmac ] = $matches;
        if ((int) $expiration < time()) {
            return null;
        }

        $user = self::find($login, 'login');
        if (! $user instanceof self) {
            return null;
        }

        $expected = hash_hmac('sha256', "$login|$expiration|$user->password", EX_AUTH_KEY);

        return hash_equals($expected, $hmac) ? $user : null;
    }

    /**
     * Issue the signed authentication cookie for the given user.
     *
     * @param User $user       The user that has just logged in.
     * @param int  $expiration Unix timestamp embedded in and validated against the signature.
     * @param bool $remember   Whether the cookie should survive browser restarts.
     * @return void
     */
    private static function setAuthCookie(User $user, int $expiration, bool $remember): void
    {
        Cookie::send(new Cookie(
            name: self::$cookieName,
            value: self::signAuthCookie($user, $expiration),
            expires: $remember ? $expiration : 0,
            path: '/',
            secure: Cookie::isSecureRequest(),
            httpOnly: true,
            sameSite: Cookie::SAME_SITE_LAX,
        ));
    }

    /**
     * Discard the authentication cookie.
     *
     * @return void
     */
    private static function clearAuthCookie(): void
    {
        Cookie::send(new Cookie(
            name: self::$cookieName,
            path: '/',
            secure: Cookie::isSecureRequest(),
            httpOnly: true,
            sameSite: Cookie::SAME_SITE_LAX,
        ));
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
