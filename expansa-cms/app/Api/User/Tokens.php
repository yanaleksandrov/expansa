<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Facades\Access;
use Expansa\Facades\Db;

/**
 * Personal API tokens, the `api_keys` table: `Authorization: Bearer exp_<prefix>_<secret>` acts as the
 * owner, narrowed to the scopes of the token, a subset of the owner's permissions. The table keeps only
 * a hash of the token, the prefix stays readable to tell tokens apart. A request with a token reaches
 * only the endpoints that name a permission of its scopes, never the profile and security ones.
 */
final class Tokens
{
    /**
     * Seconds between updates of the last use.
     */
    private const int TOUCH_INTERVAL = 300;

    /**
     * Scopes of the token of the request, null for a request without one.
     *
     * @var string[]|null
     */
    private static ?array $scopes = null;

    /**
     * Create a token; it is shown once.
     *
     * @param User     $user
     * @param string   $name   Label of the token.
     * @param string[] $scopes Permissions; those the user doesn't have are dropped.
     * @param int      $days   Lifetime, 0 for none.
     * @return string The token.
     */
    public static function create(User $user, string $name, array $scopes, int $days): string
    {
        $prefix = bin2hex(random_bytes(4));
        $token  = "exp_{$prefix}_" . bin2hex(random_bytes(20));
        $scopes = array_values(array_filter(array_unique($scopes), fn (string $scope) => Access::allows($user, $scope)));

        Db::insert('api_keys', [
            'user_id'    => $user->id,
            'name'       => mb_substr(trim($name), 0, 100) ?: 'API token',
            'prefix'     => $prefix,
            'key_hash'   => hash('sha256', $token),
            'active'     => 1,
            'abilities'  => json_encode($scopes),
            'expires_at' => $days > 0 ? date('Y-m-d H:i:s', time() + $days * 86400) : null,
        ]);
        Events::record($user, 'token_created', ['name' => $name]);

        return $token;
    }

    /**
     * Get the tokens of a user for the profile, newest first.
     *
     * @param User $user
     * @return array<int, array<string, mixed>> `id`, `name`, `prefix`, `scopes`, `last_used_at`, `expires_at`.
     */
    public static function all(User $user): array
    {
        $rows = Db::select('api_keys', ['id [Int]', 'name', 'prefix', 'abilities', 'last_used_at', 'expires_at'], [
            'user_id' => $user->id,
            'ORDER'   => ['id' => 'DESC'],
        ]) ?? [];

        return array_map(fn (array $row) => ['scopes' => (array) json_decode((string) $row['abilities'], true)] + $row, $rows);
    }

    /**
     * Revoke a token of a user.
     *
     * @param User $user
     * @param int  $id
     * @return bool
     */
    public static function delete(User $user, int $id): bool
    {
        $isDeleted = (Db::delete('api_keys', ['id' => $id, 'user_id' => $user->id])?->rowCount() ?? 0) > 0;
        if ($isDeleted) {
            Events::record($user, 'token_revoked');
        }

        return $isDeleted;
    }

    /**
     * Find the owner of a token and remember its scopes for the request.
     *
     * @param string $token
     * @return User|null Null for an unknown, revoked or expired token, or a disabled owner.
     */
    public static function authenticate(string $token): ?User
    {
        if (! preg_match('/^exp_[a-f0-9]{8}_[a-f0-9]{40}$/', $token)) {
            return null;
        }

        $columns = ['id', 'user_id', 'abilities', 'last_used_at', 'expires_at'];
        $row     = Db::get('api_keys', $columns, ['key_hash' => hash('sha256', $token), 'active' => 1]);
        $user    = is_array($row) ? User::find((int) $row['user_id']) : null;

        $isExpired = is_array($row) && $row['expires_at'] !== null && strtotime($row['expires_at']) < time();
        if (! $user instanceof User || $isExpired || $user->status !== User::STATUS_ACTIVE) {
            return null;
        }

        if (strtotime((string) $row['last_used_at']) < time() - self::TOUCH_INTERVAL) {
            Db::update('api_keys', ['last_used_at' => date('Y-m-d H:i:s')], ['id' => $row['id']]);
        }

        self::$scopes = (array) json_decode((string) $row['abilities'], true);

        return $user;
    }

    /**
     * Whether the request may use a permission: always without a token, within the scopes with one.
     *
     * @param string $permission
     * @return bool
     */
    public static function allows(string $permission): bool
    {
        return self::$scopes === null || in_array($permission, self::$scopes, true);
    }
}
