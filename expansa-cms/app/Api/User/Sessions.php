<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\User;
use Expansa\Auth\Contracts\Identity;
use Expansa\Auth\Contracts\Sessions as SessionStore;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use LogicException;

/**
 * Sign-ins of users, the `user_sessions` table: one row per device, so the profile can list
 * the devices and sign out any of them. The table keeps only a hash of the session ID: the ID
 * itself lives in the signed token of the browser.
 */
final class Sessions implements SessionStore
{
    /**
     * Seconds between updates of the last use, so a request doesn't write every time.
     */
    private const int TOUCH_INTERVAL = 300;

    /**
     * Display names of browsers by user agent fragment, the first match wins.
     */
    private const array BROWSERS = [
        'Edg/'      => 'Edge',
        'OPR/'      => 'Opera',
        'YaBrowser' => 'Yandex Browser',
        'Firefox/'  => 'Firefox',
        'Chrome/'   => 'Chrome',
        'Safari/'   => 'Safari',
    ];

    /**
     * Display names of systems by user agent fragment, the first match wins.
     */
    private const array SYSTEMS = [
        'iPhone'   => 'iPhone',
        'iPad'     => 'iPad',
        'Android'  => 'Android',
        'Windows'  => 'Windows',
        'Mac OS X' => 'macOS',
        'CrOS'     => 'ChromeOS',
        'Linux'    => 'Linux',
    ];

    public function create(Identity $user, int $expires): string
    {
        $id = bin2hex(random_bytes(16));

        // sign-ins are rare enough to clean up the expired rows of everyone here
        Db::delete('user_sessions', ['expires_at[<]' => date('Y-m-d H:i:s')]);
        Db::insert('user_sessions', [
            'user_id'    => $this->userId($user),
            'token'      => hash('sha256', $id),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'ip'         => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'used_at'    => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', $expires),
        ]);

        return $id;
    }

    public function isActive(string $id, Identity $user): bool
    {
        $row = Db::get('user_sessions', ['id', 'user_id', 'used_at', 'expires_at'], ['token' => hash('sha256', $id)]);
        if (! is_array($row) || (int) $row['user_id'] !== $this->userId($user) || strtotime($row['expires_at']) < time()) {
            return false;
        }

        if (strtotime((string) $row['used_at']) < time() - self::TOUCH_INTERVAL) {
            $touched = ['used_at' => date('Y-m-d H:i:s'), 'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? '')];
            Db::update('user_sessions', $touched, ['id' => $row['id']]);
        }

        return true;
    }

    public function delete(string $id): void
    {
        Db::delete('user_sessions', ['token' => hash('sha256', $id)]);
    }

    /**
     * Get the devices a user is signed in on for the profile, the current one first.
     *
     * @param User $user
     * @return array<int, array{id: int, device: string, ip: string, created_at: string, used_at: string, current: bool}>
     */
    public static function all(User $user): array
    {
        $current = hash('sha256', Auth::getSessionId());
        $rows    = Db::select('user_sessions', ['id [Int]', 'token', 'user_agent', 'ip', 'created_at', 'used_at'], [
            'user_id'        => $user->id,
            'expires_at[>=]' => date('Y-m-d H:i:s'),
            'ORDER'          => ['used_at' => 'DESC'],
        ]) ?? [];

        $sessions = array_map(fn (array $row) => [
            'id'         => $row['id'],
            'device'     => self::describe($row['user_agent']),
            'ip'         => $row['ip'],
            'created_at' => $row['created_at'],
            'used_at'    => $row['used_at'],
            'current'    => hash_equals($row['token'], $current),
        ], $rows);

        usort($sessions, fn (array $a, array $b) => $b['current'] <=> $a['current']);

        return $sessions;
    }

    /**
     * Sign out a device of a user.
     *
     * @param User $user Owner, a foreign ID removes nothing.
     * @param int  $id   Row ID.
     * @return bool Whether the device was signed out.
     */
    public static function deleteById(User $user, int $id): bool
    {
        return (Db::delete('user_sessions', ['id' => $id, 'user_id' => $user->id])?->rowCount() ?? 0) > 0;
    }

    /**
     * Sign out every device of a user except the current one.
     *
     * @param User $user
     * @return int Number of devices signed out.
     */
    public static function deleteOthers(User $user): int
    {
        return Db::delete('user_sessions', [
            'user_id'  => $user->id,
            'token[!]' => hash('sha256', Auth::getSessionId()),
        ])?->rowCount() ?? 0;
    }

    /**
     * Delete the expired sign-ins of all users, run daily by the scheduler.
     *
     * @return int Number of rows deleted.
     */
    public static function prune(): int
    {
        return Db::delete('user_sessions', ['expires_at[<]' => date('Y-m-d H:i:s')])?->rowCount() ?? 0;
    }

    /**
     * Human name of a device by its user agent: "Chrome, Windows".
     *
     * @param string $userAgent
     * @return string
     */
    public static function describe(string $userAgent): string
    {
        $find = fn (array $names) => array_find($names, fn (string $_, string $fragment) => str_contains($userAgent, $fragment));

        return implode(', ', array_filter([$find(self::BROWSERS), $find(self::SYSTEMS)])) ?: t('Unknown device');
    }

    /**
     * Row ID of the user.
     *
     * @param Identity $user
     * @return int
     * @throws LogicException For an identity that is not a User.
     */
    private function userId(Identity $user): int
    {
        return $user instanceof User ? $user->id : throw new LogicException('Sessions are kept only for users.');
    }
}
