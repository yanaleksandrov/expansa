<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Comment;
use App\Models\User;
use App\Post\Status;
use App\Post\Type;
use App\Support\Values\Change;
use App\Support\Values\Line;
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
     * Columns of short text whose values `updated` keeps as `changes`, to show what they were and became;
     * the content keeps its length in characters, a password only whether it is set.
     */
    private const array DIFF = [
        'title'        => true,
        'slug'         => true,
        'discussion'   => true,
        'parent_id'    => true,
        'created_at'   => true,
        'password'     => true,
        'content'      => true,
        'name'         => true,
        'description'  => true,
        'rating'       => true,
        'author_name'  => true,
        'author_email' => true,
    ];

    /**
     * Characters of a value kept in `changes`, the rest is cut with an ellipsis.
     */
    private const int LENGTH = 120;

    /**
     * Record an event of an entity by the current user.
     *
     * @param string               $table   Table of the entity without the prefix: `pages`, `comments`.
     * @param int                  $id
     * @param string               $event   Snake case name: `updated`, `status_changed`, `trashed`...
     * @param array<string, mixed> $details Short facts shown with the event, e.g. `['fields' => ['title']]`;
     *                                      `changes` of `updated` are `[column => [from, to]]`.
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
                $merged  = (array) json_decode($last['details'], true);
                $changes = (array) ($merged['changes'] ?? []);
                foreach ($details['changes'] ?? [] as $column => [$from, $to]) {
                    $changes[$column] = [$changes[$column][0] ?? $from, $to];
                }
                // a new password is masked the same as the old one, a new content may be as long: they stay changes
                $changes = array_filter($changes, fn (array $change, string $column) => $column === 'password' || $column === 'content' || $change[0] !== $change[1], ARRAY_FILTER_USE_BOTH);

                Db::update('activity', [
                    'details'    => json_encode([
                        'fields'  => array_values(array_unique([...$merged['fields'] ?? [], ...$details['fields'] ?? []])),
                        'changes' => $changes,
                    ], JSON_UNESCAPED_UNICODE),
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
     * with the names of the other changed columns and the values of the short ones. Values are compared
     * as strings, so a number read from the database as a string is not a change.
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
        if ($fields === []) {
            return;
        }

        $changes = [];
        foreach ($fields as $field) {
            if ($field === 'password') {
                $changes[$field] = [$string($before[$field] ?? '') === '' ? '' : '******', $string($after[$field]) === '' ? '' : '******'];
            } elseif ($field === 'content') {
                $changes[$field] = [mb_strlen(strip_tags($string($before[$field] ?? ''))), mb_strlen(strip_tags($string($after[$field])))];
            } elseif (isset(self::DIFF[$field])) {
                $changes[$field] = [
                    mb_strimwidth($string($before[$field] ?? ''), 0, self::LENGTH, '…'),
                    mb_strimwidth($string($after[$field]), 0, self::LENGTH, '…'),
                ];
            }
        }

        self::record($table, $id, 'updated', [
            'fields'  => $fields,
            'changes' => $changes,
        ]);
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
     * and the lines the editor adds, such as its creation or the comments of a post; an edit has
     * the changes of its short columns.
     *
     * @param string $table
     * @param int    $id
     * @param Line[] $lines Lines of the editor.
     * @param int    $limit Events read from the log.
     * @return Line[]
     */
    public static function timeline(string $table, int $id, array $lines = [], int $limit = 50): array
    {
        $rows = $id === 0 ? [] : (Db::select('activity', ['user_id [Int]', 'event', 'details', 'created_at'], [
            'entity_table' => $table,
            'entity_id'    => $id,
            'ORDER'        => ['id' => 'DESC'],
            'LIMIT'        => $limit,
        ]) ?: []);

        $names    = self::names(array_column($rows, 'user_id'));
        $statuses = self::statuses($table);
        $parents  = self::parents($table, $rows);
        foreach ($rows as $row) {
            $details = (array) json_decode((string) $row['details'], true);
            $changes = [];
            if ($row['event'] === 'status_changed') {
                $from      = (string) ($details['from'] ?? '');
                $to        = (string) ($details['to'] ?? '');
                $changes[] = new Change(t('Status'), $statuses[$from] ?? $from, $statuses[$to] ?? $to);
            }

            foreach ((array) ($details['changes'] ?? []) as $column => [$from, $to]) {
                $changes[] = new Change(self::field($column), self::value($column, (string) $from, $parents), self::value($column, (string) $to, $parents));
            }

            $lines[] = new Line(
                icon: self::icon($row['event']),
                text: self::label($table, $row['event'], $details),
                user: $names[$row['user_id']] ?? '',
                changes: $changes,
                at: $row['created_at'],
            );
        }

        usort($lines, fn (Line $a, Line $b) => strcmp($b->at, $a->at));

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
        return match ($event) {
            'updated'        => t('Edited: :fields', mb_strtolower(implode(', ', array_map(self::field(...), (array) ($details['fields'] ?? []))))),
            'status_changed' => t('Changed the status'),
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
            'author_id'    => t('Author'),
            'created_at'   => t('Created'),
            'discussion'   => t('Discussion'),
            'password'     => t('Password'),
            'rating'       => t('Rating'),
            'author_name'  => t('Name', context: 'person'),
            'author_email' => t('Email'),
            'name'         => t('Name'),
            'description'  => t('Description'),
            default        => str_replace('_', ' ', $column),
        };
    }

    /**
     * Human value of a changed column in `changes`: the name of a choice, e.g. of the discussion,
     * the title of a parent post, its ID when the post is gone, empty for none, the length of the content.
     *
     * @param string             $column
     * @param string             $value
     * @param array<int, string> $parents Titles of the parent posts by ID, see parents().
     * @return string
     */
    private static function value(string $column, string $value, array $parents = []): string
    {
        return match ($column) {
            'parent_id'  => (int) $value === 0 ? '' : ($parents[(int) $value] ?? '#' . $value),
            'content'    => t(':count characters', (int) $value),
            'created_at' => Dates::format($value),
            'discussion' => match ($value) {
                'open'   => t('Open'),
                'closed' => t('Closed'),
                default  => $value,
            },
            default      => $value,
        };
    }

    /**
     * Get the titles of the parents named in the `changes` of the events of a post in one query;
     * an entity that is not a post has none.
     *
     * @param string                           $table
     * @param array<int, array<string, mixed>> $rows  Events from the log.
     * @return array<int, string> Titles by ID, `#ID` for a post without a title.
     */
    private static function parents(string $table, array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if ($row['event'] === 'updated' && str_contains((string) $row['details'], '"parent_id"')) {
                foreach ((array) (json_decode((string) $row['details'], true)['changes']['parent_id'] ?? []) as $id) {
                    $ids[] = (int) $id;
                }
            }
        }

        $ids = array_values(array_filter(array_unique($ids)));
        if ($ids === [] || Type::get($table) === null) {
            return [];
        }

        $titles = [];
        foreach (Db::select($table, ['id [Int]', 'title'], ['id' => $ids]) ?: [] as $post) {
            $titles[$post['id']] = $post['title'] !== '' ? $post['title'] : '#' . $post['id'];
        }

        return $titles;
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
