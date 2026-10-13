<?php

declare(strict_types=1);

namespace Plugins\FileManager;

use RuntimeException;

/**
 * Trash of the file manager: every deleted entry gets its own folder `<id>/<name>` and the file
 * `<id>.json` with the original folder and the time. Explorer moves the entries in and out.
 *
 * @package Plugins\FileManager
 */
final readonly class Trash
{
    public function __construct(

        /**
         * Folder of the trash with forward slashes, created on the first deletion.
         */
        public string $folder,
    ) {}

    /**
     * Folder for a new deleted entry, with its description saved.
     *
     * @param string $path Original folder relative to the root.
     * @param string $name Name of the entry.
     * @return string Absolute folder to move the entry into.
     */
    public function reserve(string $path, string $name): string
    {
        $id     = date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $folder = "$this->folder/$id";
        if (! @mkdir($folder, 0755, true)) {
            throw new RuntimeException("Failed to create the trash folder $folder.");
        }

        $meta = ['path' => $path, 'name' => $name, 'deleted' => time()];
        file_put_contents("$folder.json", json_encode($meta, JSON_UNESCAPED_UNICODE));

        return $folder;
    }

    /**
     * Deleted entries, newest first.
     *
     * @return list<array{id: string, name: string, path: string, deleted: int, entry: string}>
     */
    public function all(): array
    {
        $entries = [];
        foreach (glob("$this->folder/*.json") ?: [] as $file) {
            $entry = $this->get(basename($file, '.json'));
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        usort($entries, static fn (array $a, array $b) => $b['deleted'] <=> $a['deleted']);

        return $entries;
    }

    /**
     * Deleted entry by id.
     *
     * @param string $id
     * @return array{id: string, name: string, path: string, deleted: int, entry: string}|null `entry` is its absolute path.
     */
    public function get(string $id): ?array
    {
        if (! preg_match('/^\d{14}-[a-f0-9]{8}$/', $id) || ! is_file("$this->folder/$id.json")) {
            return null;
        }

        $meta  = (array) json_decode((string) file_get_contents("$this->folder/$id.json"), true);
        $name  = (string) ($meta['name'] ?? '');
        $entry = "$this->folder/$id/$name";
        if ($name === '' || (! file_exists($entry) && ! is_link($entry))) {
            return null;
        }

        return [
            'id'      => $id,
            'name'    => $name,
            'path'    => (string) ($meta['path'] ?? ''),
            'deleted' => (int) ($meta['deleted'] ?? 0),
            'entry'   => $entry,
        ];
    }

    /**
     * Drop the description and the empty folder of an entry moved out of the trash.
     *
     * @param string $id
     * @return void
     */
    public function forget(string $id): void
    {
        @unlink("$this->folder/$id.json");
        @rmdir("$this->folder/$id");
    }
}
