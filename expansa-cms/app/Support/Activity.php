<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Comment;
use App\Models\User;
use App\Post\Status;
use App\Post\Type;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;

/**
 * Activity log of the site entities, the `activity` table: who changed a post, a comment, a term
 * and what, for the Activity tab of their editors. An entity is addressed as in `slugs`: by its table
 * and ID. Models record their own changes, so the editors, bulk actions and plugins get the same log.
 *
 * Plugins describe their events with the `activityLabel` and `activityIcon` filters
 * and name their statuses with `activityStatuses`.
 */
final class Activity
{
    /**
     * Seconds in which the edits of one user add up to their last `updated` event instead of a new row,
     * so saving several times in a row leaves one line.
     */
    private const int MERGE = 600;

    /**
     * Record an event of an entity by the current user.
     *
     * @param string               $table   Table of the entity without the prefix: `pages`, `comments`.
     * @param int                  $id
     * @param string               $event   Snake case name: `updated`, `status_changed`, `trashed`...
     * @param array<string, mixed> $details Short facts shown with the event, e.g. `['fields' => ['title']]`.
     * @return void
     */
    public static function record(string $table, int $id, string $event, array $details = []): void
    {
        if ($id === 0) {
            return;
        }

        $userId = User::current()->id ?? 0;

        if ($event === 'updated') {
            $last = Db::get('activity', ['id [Int]', 'user_id [Int]', 'event', 'details', 'created_at'], [
                'entity_table' => $table,
                'entity_id'    => $id,
                'ORDER'        => ['id' => 'DESC'],
            ]);
            if (
                is_array($last)
                && $last['event'] === 'updated'
                && $last['user_id'] === $userId
                && strtotime($last['created_at']) > time() - self::MERGE
            ) {
                $fields = (array) (json_decode($last['details'], true)['fields'] ?? []);
                Db::update('activity', [
                    'details'    => json_encode(['fields' => array_values(array_unique([...$fields, ...$details['fields'] ?? []]))]),
                    'created_at' => date('Y-m-d H:i:s'),
                ], ['id' => $last['id']]);

                return;
            }
        }

        Db::insert('activity', [
            'entity_table' => $table,
            'entity_id'    => $id,
            'user_id'      => $userId,
            'event'        => $event,
            'details'      => $details === [] ? '' : (string) json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Record the changes of an entity on saving: `status_changed` with both statuses, `updated`
     * with the names of the other changed columns. Values are compared as strings, so a number
     * read from the database as a string is not a change.
     *
     * @param string               $table
     * @param int                  $id
     * @param array<string, mixed> $before Columns before saving.
     * @param array<string, mixed> $after  Saved columns; the keys missing in it are not compared.
     * @return void
     */
    public static function changes(string $table, int $id, array $before, array $after): void
    {
        $string  = fn (mixed $value): string => is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value);
        $changed = [];
        foreach ($after as $key => $value) {
            if ($string($value) !== $string($before[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        if (in_array('status', $changed, true)) {
            self::record($table, $id, 'status_changed', ['from' => (string) ($before['status'] ?? ''), 'to' => (string) $after['status']]);
        }

        $fields = array_values(array_diff($changed, ['status', 'updated_at']));
        if ($fields !== []) {
            self::record($table, $id, 'updated', ['fields' => $fields]);
        }
    }

    /**
     * Delete the log of an entity deleted permanently.
     *
     * @param string $table
     * @param int    $id
     * @return void
     */
    public static function forget(string $table, int $id): void
    {
        Db::delete('activity', ['entity_table' => $table, 'entity_id' => $id]);
    }

    /**
     * Get the timeline of an entity for its Activity tab, newest first: the recorded events
     * and the lines the editor adds, such as its creation or the comments of a post.
     *
     * @param string                                                                    $table
     * @param int                                                                       $id
     * @param array<int, array{icon: string, user: string, text: string, note: string, at: string}> $lines
     * @param int                                                                       $limit Events read from the log.
     * @return array<int, array{icon: string, user: string, text: string, note: string, at: string}>
     */
    public static function timeline(string $table, int $id, array $lines = [], int $limit = 50): array
    {
        $rows = $id === 0 ? [] : (Db::select('activity', ['user_id [Int]', 'event', 'details', 'created_at'], [
            'entity_table' => $table,
            'entity_id'    => $id,
            'ORDER'        => ['id' => 'DESC'],
            'LIMIT'        => $limit,
        ]) ?: []);

        $names = self::names(array_column($rows, 'user_id'));
        foreach ($rows as $row) {
            $details = (array) json_decode((string) $row['details'], true);
            $lines[] = [
                'icon' => self::icon($row['event']),
                'user' => $names[$row['user_id']] ?? '',
                'text' => self::label($table, $row['event'], $details),
                'note' => '',
                'at'   => $row['created_at'],
            ];
        }

        usort($lines, fn (array $a, array $b) => strcmp($b['at'], $a['at']));

        return $lines;
    }

    /**
     * Get the names of users by their IDs in one query, the display name or the login.
     *
     * @param int[] $ids
     * @return array<int, string> Names by ID; the IDs of deleted users are missing.
     */
    public static function names(array $ids): array
    {
        $ids = array_values(array_filter(array_unique($ids)));
        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach (Db::select('users', ['id [Int]', 'showname', 'login'], ['id' => $ids]) ?: [] as $user) {
            $names[$user['id']] = $user['showname'] ?: $user['login'];
        }

        return $names;
    }

    /**
     * Human description of an event.
     *
     * @param string               $table
     * @param string               $event
     * @param array<string, mixed> $details
     * @return string Plain text, escape it for HTML.
     */
    public static function label(string $table, string $event, array $details = []): string
    {
        $statuses = self::statuses($table);
        $status   = fn (string $key) => $statuses[(string) ($details[$key] ?? '')] ?? (string) ($details[$key] ?? '');

        return match ($event) {
            'updated'        => t('Edited: :fields', mb_strtolower(implode(', ', array_map(self::field(...), (array) ($details['fields'] ?? []))))),
            'status_changed' => t('Status: :from → :to', $status('from'), $status('to')),
            'trashed'        => t('Moved to the trash'),
            'restored'       => t('Restored from the trash'),
            'duplicated'     => t('Copied to «:title»', (string) ($details['title'] ?? '')),
            default          => (string) Hook::call('activityLabel', $event, $table, $event, $details),
        };
    }

    /**
     * Phosphor icon of an event.
     *
     * @param string $event
     * @return string Classes of the icon.
     */
    public static function icon(string $event): string
    {
        return match ($event) {
            'updated'        => 'ph ph-pencil-simple',
            'status_changed' => 'ph ph-circle-dashed',
            'trashed'        => 'ph ph-trash',
            'restored'       => 'ph ph-arrow-counter-clockwise',
            'duplicated'     => 'ph ph-copy',
            default          => (string) Hook::call('activityIcon', 'ph ph-dot-outline', $event),
        };
    }

    /**
     * Human name of a changed column.
     *
     * @param string $column
     * @return string
     */
    private static function field(string $column): string
    {
        return match ($column) {
            'title'        => t('Title'),
            'content'      => t('Content'),
            'slug'         => t('Slug'),
            'parent_id'    => t('Parent'),
            'discussion'   => t('Discussion'),
            'password'     => t('Password'),
            'rating'       => t('Rating'),
            'author_name'  => t('Name'),
            'author_email' => t('Email'),
            'name'         => t('Name'),
            'description'  => t('Description'),
            default        => str_replace('_', ' ', $column),
        };
    }

    /**
     * Names of the statuses of an entity by its table, for `status_changed`.
     *
     * @param string $table
     * @return array<string, string>
     */
    private static function statuses(string $table): array
    {
        return match (true) {
            $table === 'comments'      => Comment::statuses(),
            Type::get($table) !== null => Status::getStatuses(),
            default                    => (array) Hook::call('activityStatuses', [], $table),
        };
    }
}
