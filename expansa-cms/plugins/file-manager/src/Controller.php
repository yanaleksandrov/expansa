<?php

declare(strict_types=1);

namespace Plugins\FileManager;

use App\Api\User\Events;
use App\Http\Can;
use App\Models\User;
use Expansa\Facades\Db;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Request;
use Expansa\Http\Response;

/**
 * API of the file manager: `POST /api/file-manager/{method}` in kebab case, `GET` for download
 * and preview. `path` is the current folder relative to the root, `names` a JSON list of selected
 * entries. A change returns the listing of the current folder, as list() does, and goes to the
 * activity log of the user.
 *
 * @package Plugins\FileManager
 */
final readonly class Controller
{
    /**
     * Methods available as POST endpoints.
     */
    public const array ENDPOINTS = [
        'list',
        'folders',
        'search',
        'details',
        'size',
        'read',
        'conflicts',
        'createFile',
        'createFolder',
        'upload',
        'paste',
        'rename',
        'chmod',
        'delete',
        'archive',
        'extract',
        'trash',
        'restore',
        'purge',
        'log',
        'settings',
    ];

    /**
     * Methods available as GET endpoints: they send a file.
     */
    public const array DOWNLOADS = ['download', 'preview'];

    /**
     * Events of the activity log, see Events::record().
     */
    public const array EVENTS = [
        'file_manager_created',
        'file_manager_uploaded',
        'file_manager_copied',
        'file_manager_moved',
        'file_manager_renamed',
        'file_manager_chmod',
        'file_manager_deleted',
        'file_manager_restored',
        'file_manager_purged',
        'file_manager_archived',
        'file_manager_extracted',
        'file_manager_downloaded',
        'file_manager_settings',
    ];

    /**
     * Saved settings.
     */
    private Settings $settings;

    /**
     * Operations inside the root folder.
     */
    private Explorer $explorer;

    public function __construct()
    {
        $this->settings = Settings::load();
        $this->explorer = $this->settings->createExplorer();
    }

    #[Can('manage_options')]
    public function list(Request $request): array
    {
        return $this->explorer->list($request->getString('path'));
    }

    #[Can('manage_options')]
    public function folders(Request $request): array
    {
        return ['folders' => $this->explorer->folders($request->getString('path'))];
    }

    #[Can('manage_options')]
    public function search(Request $request): array
    {
        return $this->explorer->search($request->getString('path'), $request->getString('query'));
    }

    #[Can('manage_options')]
    public function details(Request $request): array
    {
        return $this->explorer->details($request->getString('path'), $request->getString('name'));
    }

    #[Can('manage_options')]
    public function size(Request $request): array
    {
        return $this->explorer->size($request->getString('path'), $request->getString('name'));
    }

    #[Can('manage_options')]
    public function read(Request $request): array
    {
        return ['text' => $this->explorer->read($request->getString('path'), $request->getString('name'))];
    }

    #[Can('manage_options')]
    public function conflicts(Request $request): array
    {
        return ['names' => $this->explorer->conflicts($request->getString('to'), $this->names($request))];
    }

    #[Can('manage_options')]
    public function createFile(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_created',
            fn () => $this->explorer->create($request->getString('path'), $request->getString('name')),
        );
    }

    #[Can('manage_options')]
    public function createFolder(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_created',
            fn () => $this->explorer->create($request->getString('path'), $request->getString('name'), folder: true),
        );
    }

    #[Can('manage_options')]
    public function upload(Request $request): array
    {
        $files = (array) ($request->files['files'] ?? []);

        return $this->change(
            $request,
            'file_manager_uploaded',
            fn () => $this->explorer->upload($this->to($request), $files, $this->conflict($request)),
            ['path' => $this->to($request), 'names' => array_values((array) ($files['name'] ?? []))],
        );
    }

    #[Can('manage_options')]
    public function paste(Request $request): array
    {
        $move = $request->getBool('move');

        return $this->change(
            $request,
            $move ? 'file_manager_moved' : 'file_manager_copied',
            fn () => $this->explorer->paste(
                $request->getString('from'),
                $this->names($request),
                $this->to($request),
                $move,
                $this->conflict($request),
            ),
            ['path' => $request->getString('from'), 'names' => $this->names($request), 'to' => $this->to($request)],
        );
    }

    #[Can('manage_options')]
    public function rename(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_renamed',
            fn () => $this->explorer->rename($request->getString('path'), $request->getString('name'), $request->getString('to')),
            ['path' => $request->getString('path'), 'names' => [$request->getString('name')], 'to' => $request->getString('to')],
        );
    }

    #[Can('manage_options')]
    public function chmod(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_chmod',
            fn () => $this->explorer->chmod($request->getString('path'), $this->names($request), $request->getString('mode')),
            ['path' => $request->getString('path'), 'names' => $this->names($request), 'to' => $request->getString('mode')],
        );
    }

    #[Can('manage_options')]
    public function delete(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_deleted',
            fn () => $this->explorer->delete($request->getString('path'), $this->names($request)),
        );
    }

    #[Can('manage_options')]
    public function archive(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_archived',
            fn () => $this->explorer->archive($request->getString('path'), $this->names($request), $request->getString('name')),
            ['path' => $request->getString('path'), 'names' => $this->names($request), 'to' => $request->getString('name')],
        );
    }

    #[Can('manage_options')]
    public function extract(Request $request): array
    {
        return $this->change(
            $request,
            'file_manager_extracted',
            fn () => $this->explorer->extract($request->getString('path'), $request->getString('name')),
        );
    }

    #[Can('manage_options')]
    public function trash(): array
    {
        return ['items' => $this->explorer->trashed()];
    }

    #[Can('manage_options')]
    public function restore(Request $request): array
    {
        $this->writable();
        $this->record('file_manager_restored', ['names' => $this->trashNames($this->names($request))]);
        $this->explorer->restore($this->names($request));

        return $this->trash();
    }

    #[Can('manage_options')]
    public function purge(Request $request): array
    {
        $this->writable();
        $ids = $this->names($request);
        $this->record('file_manager_purged', ['names' => $ids === [] ? [t('Everything')] : $this->trashNames($ids)]);
        $this->explorer->purge($ids);

        return $this->trash();
    }

    /**
     * Recent actions of all users in the file manager, newest first.
     *
     * @return array{items: list<array<string, string>>}
     */
    #[Can('manage_options')]
    public function log(): array
    {
        $rows = Db::select('users_events', ['[>]users' => ['user_id' => 'id']], [
            'users_events.event',
            'users_events.details',
            'users_events.created_at',
            'users.login',
        ], [
            'users_events.event' => self::EVENTS,
            'ORDER'              => ['users_events.id' => 'DESC'],
            'LIMIT'              => 200,
        ]) ?? [];

        $items = array_map(fn (array $row) => [
            'user'   => (string) $row['login'],
            'action' => (string) self::label((string) $row['event']),
            'target' => self::target((array) json_decode((string) $row['details'], true)),
            'date'   => (string) $row['created_at'],
        ], $rows);

        return ['items' => $items];
    }

    #[Can('manage_options')]
    public function settings(Request $request, Response $response): Response
    {
        $settings = Settings::save($request->post);
        $this->record('file_manager_settings', ['names' => [$settings->root ?: EX_PATH]]);

        return $response->notify(t('Options updated successfully'))->reload(1000);
    }

    #[Can('manage_options')]
    public function download(Request $request): never
    {
        $this->record('file_manager_downloaded', ['path' => $request->getString('path'), 'names' => $this->names($request)]);
        $this->explorer->download($request->getString('path'), $this->names($request));
    }

    #[Can('manage_options')]
    public function preview(Request $request): never
    {
        $this->explorer->preview($request->getString('path'), $request->getString('name'));
    }

    /**
     * Name of an event of the file manager in the activity log, null for other events.
     *
     * @param string $event
     * @return string|null
     */
    public static function label(string $event): ?string
    {
        return match ($event) {
            'file_manager_created'    => t('Created in the file manager'),
            'file_manager_uploaded'   => t('Uploaded in the file manager'),
            'file_manager_copied'     => t('Copied in the file manager'),
            'file_manager_moved'      => t('Moved in the file manager'),
            'file_manager_renamed'    => t('Renamed in the file manager'),
            'file_manager_chmod'      => t('Permissions changed in the file manager'),
            'file_manager_deleted'    => t('Deleted in the file manager'),
            'file_manager_restored'   => t('Restored from the trash'),
            'file_manager_purged'     => t('Deleted from the trash'),
            'file_manager_archived'   => t('Archived in the file manager'),
            'file_manager_extracted'  => t('Extracted in the file manager'),
            'file_manager_downloaded' => t('Downloaded from the file manager'),
            'file_manager_settings'   => t('File manager settings changed'),
            default                   => null,
        };
    }

    /**
     * Run a change and record it, then list the current folder.
     *
     * @param Request              $request
     * @param string               $event   Event of the activity log.
     * @param callable(): void     $change
     * @param array<string, mixed> $details Default: `path` and `names`, or `name` when there are no names.
     * @return array<string, mixed>
     */
    private function change(Request $request, string $event, callable $change, ?array $details = null): array
    {
        $this->writable();
        $change();

        $names = $this->names($request) ?: [$request->getString('name')];
        $this->record($event, $details ?? ['path' => $request->getString('path'), 'names' => $names]);

        return $this->list($request);
    }

    /**
     * Stop a change in the read-only mode.
     *
     * @return void
     */
    private function writable(): void
    {
        if ($this->settings->readonly) {
            throw new HttpError(403, t('The file manager is read-only.'));
        }
    }

    /**
     * Add an event to the activity log of the current user.
     *
     * @param string               $event
     * @param array<string, mixed> $details
     * @return void
     */
    private function record(string $event, array $details): void
    {
        $user = User::current();
        if ($user !== null) {
            $details['names'] = array_slice((array) ($details['names'] ?? []), 0, 20);
            Events::record($user, $event, $details);
        }
    }

    /**
     * Short description of the entries of an event: `app/Api: a.php, b.php → app`.
     *
     * @param array<string, mixed> $details
     * @return string
     */
    private static function target(array $details): string
    {
        $path   = (string) ($details['path'] ?? '');
        $names  = implode(', ', array_map(strval(...), (array) ($details['names'] ?? [])));
        $target = ($path === '' ? '' : "$path: ") . $names;

        return isset($details['to']) ? "$target → {$details['to']}" : $target;
    }

    /**
     * Original paths of trash entries, for the activity log.
     *
     * @param string[] $ids
     * @return string[]
     */
    private function trashNames(array $ids): array
    {
        $names = [];
        foreach ($ids as $id) {
            $item = $this->explorer->trash?->get($id);
            if ($item !== null) {
                $names[] = ltrim("{$item['path']}/{$item['name']}", '/');
            }
        }

        return $names;
    }

    /**
     * Target folder of a paste or an upload: `to`, the current folder without it.
     *
     * @param Request $request
     * @return string
     */
    private function to(Request $request): string
    {
        return $request->has('to') ? $request->getString('to') : $request->getString('path');
    }

    /**
     * Conflict mode from the request, `keep` by default.
     *
     * @param Request $request
     * @return Conflict
     */
    private function conflict(Request $request): Conflict
    {
        return Conflict::tryFrom($request->getString('conflict')) ?? Conflict::Keep;
    }

    /**
     * Selected entries from the `names` JSON list.
     *
     * @param Request $request
     * @return string[]
     */
    private function names(Request $request): array
    {
        $names = json_decode($request->getString('names'), true);

        return is_array($names) ? array_values(array_filter($names, is_string(...))) : [];
    }
}
