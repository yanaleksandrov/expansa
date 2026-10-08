<?php

declare(strict_types=1);

namespace App\Api\User;

use App\Models\Option;
use App\Models\User;
use Expansa\Facades\Db;

/**
 * Security log of users, the `user_events` table: sign-ins, failures, sign-outs and changes
 * of sign-in methods, with the IP and the device. The profile shows the recent ones, so the owner
 * notices what they did not do. Rows older than the days of the Security settings are dropped.
 */
final class Events
{
    /**
     * Days an event is kept until the Security settings say otherwise.
     */
    private const int RETENTION = 90;

    /**
     * Record an event of a user.
     *
     * @param User                 $user
     * @param string               $event   Snake case name: `sign_in`, `password_changed`...
     * @param array<string, mixed> $details Short facts shown with the event, e.g. `['method' => 'passkey']`.
     * @return void
     */
    public static function record(User $user, string $event, array $details = []): void
    {
        Db::insert('user_events', [
            'user_id'    => $user->id,
            'event'      => $event,
            'details'    => $details === [] ? '' : (string) json_encode($details, JSON_UNESCAPED_UNICODE),
            'ip'         => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        // sign-ins are frequent enough to keep the table trimmed without the scheduler
        if ($event === 'sign_in') {
            self::prune();
        }
    }

    /**
     * Delete the events older than the days of the Security settings; 0 keeps them forever.
     *
     * @return int Number of rows deleted.
     */
    public static function prune(): int
    {
        $days = (int) Option::get('security.log_days', self::RETENTION);
        if ($days <= 0) {
            return 0;
        }

        return Db::delete('user_events', ['created_at[<]' => date('Y-m-d H:i:s', time() - $days * 86400)])?->rowCount() ?? 0;
    }

    /**
     * Get the recent events of a user, newest first.
     *
     * @param User $user
     * @param int  $limit
     * @return array<int, array{event: string, details: array<string, mixed>, ip: string, device: string, created_at: string}>
     */
    public static function all(User $user, int $limit = 20): array
    {
        $rows = Db::select('user_events', ['event', 'details', 'ip', 'user_agent', 'created_at'], [
            'user_id' => $user->id,
            'ORDER'   => ['id' => 'DESC'],
            'LIMIT'   => $limit,
        ]) ?? [];

        return array_map(fn (array $row) => [
            'event'      => $row['event'],
            'details'    => (array) json_decode((string) $row['details'], true),
            'ip'         => $row['ip'],
            'device'     => Sessions::describe($row['user_agent']),
            'created_at' => $row['created_at'],
        ], $rows);
    }

    /**
     * Human name of an event for the profile.
     *
     * @param string $event
     * @return string
     */
    public static function label(string $event): string
    {
        return match ($event) {
            'sign_in'               => t('Signed in'),
            'sign_in_failed'        => t('Wrong password'),
            'sign_in_locked'        => t('Sign-in locked after wrong passwords'),
            'sign_out'              => t('Signed out'),
            'session_revoked'       => t('Signed out of a device'),
            'sessions_revoked'      => t('Signed out of other devices'),
            'password_changed'      => t('Password changed'),
            'password_reset'        => t('Password reset requested'),
            'email_changed'         => t('Email changed'),
            'email_verified'        => t('Email confirmed'),
            'passkey_added'         => t('Passkey added'),
            'passkey_removed'       => t('Passkey removed'),
            'provider_connected'    => t('Account connected'),
            'provider_disconnected' => t('Account disconnected'),
            'account_secured'       => t('Signed out everywhere after an unknown sign-in'),
            'two_factor_enabled'    => t('Two-factor authentication turned on'),
            'two_factor_disabled'   => t('Two-factor authentication turned off'),
            'two_factor_failed'     => t('Wrong two-factor code'),
            'recovery_codes_new'    => t('New recovery codes'),
            'recovery_code_used'    => t('Recovery code used'),
            'account_enabled'       => t('Account turned on'),
            'account_disabled'      => t('Account disabled'),
            'impersonation_started' => t('Signed in as another user'),
            'impersonation_stopped' => t('Stopped signing in as another user'),
            'token_created'         => t('API token created'),
            'token_revoked'         => t('API token revoked'),
            default                 => $event,
        };
    }
}
