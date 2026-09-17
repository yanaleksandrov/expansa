<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits;

/**
 * A cache provider backed by plain files on disk — one file per key, one directory per group.
 * Needs no extension or server, just a writable directory; survives past a single process like
 * Database/Redis/Memcached, at the cost of a filesystem round-trip per call.
 */
class File implements Provider
{
    use Traits;

    public function __construct(private readonly string $directory = EX_PATH . 'storage/cache/')
    {
    }

    /**
     * $expiry accepts an absolute DateTime or a relative time string (e.g. "+1 day").
     */
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed
    {
        if (isset(self::$locks[ $group ][ $key ])) {
            return false;
        }

        if (is_string($expiry)) {
            $expiry = new DateTime($expiry);
        }

        $entry = $this->read($key, $group);

        if ($entry !== null) {
            return $entry['value'];
        }

        $this->write($key, $group, $value, $expiry?->getTimestamp());

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     */
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        $this->write($key, $group, $value, null);

        return $value;
    }

    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        $entry = $this->read($key, $group);

        if ($entry !== null) {
            return $entry['value'];
        }

        if ($callback !== null) {
            return $this->add($key, $callback(), $group);
        }

        return null;
    }

    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    public function suspend(callable $callback, string $key, string $group = 'default'): void
    {
        self::$locks[ $group ][ $key ] = true;

        $callback();

        unset(self::$locks[ $group ][ $key ]);
    }

    public function forget(string $key = '', string $group = 'default'): bool
    {
        if ($key !== '') {
            @unlink($this->path($key, $group));

            return true;
        }

        $this->removeDirectory($this->directory($group));

        return true;
    }

    /**
     * Increases the value of a key by a given amount, preserving its expiry if any.
     */
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key === '') {
            return false;
        }

        $entry = $this->read($key, $group);

        if ($entry === null || ! is_numeric($entry['value'])) {
            return false;
        }

        $this->write($key, $group, $entry['value'] + $amount, $entry['expiry']);

        return true;
    }

    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    /**
     * Walks every group directory and deletes any file whose expiry has already passed. Not
     * called automatically — wire it into a Scheduler job if the directory is expected to
     * accumulate expired entries that are never read (and so never lazily evicted).
     */
    public function purgeExpired(): void
    {
        foreach (glob(rtrim($this->directory, '/\\') . '/*', GLOB_ONLYDIR) ?: [] as $groupDir) {
            foreach (glob($groupDir . '/*.cache') ?: [] as $path) {
                $entry = @unserialize((string) file_get_contents($path));

                if (is_array($entry) && $entry['expiry'] !== null && $entry['expiry'] <= time()) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * Reads an entry, transparently deleting and returning null if it has already expired.
     *
     * @return array{value: mixed, expiry: int|null}|null
     */
    private function read(string $key, string $group): ?array
    {
        $path = $this->path($key, $group);

        if (! is_file($path)) {
            return null;
        }

        $entry = @unserialize((string) file_get_contents($path));

        if (! is_array($entry) || ! array_key_exists('value', $entry)) {
            return null;
        }

        if ($entry['expiry'] !== null && $entry['expiry'] <= time()) {
            @unlink($path);

            return null;
        }

        return $entry;
    }

    /**
     * Writes via a temp file + rename, so a reader never sees a half-written file.
     */
    private function write(string $key, string $group, mixed $value, ?int $expiry): void
    {
        $dir = $this->directory($group);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $this->path($key, $group);
        $tmp  = $path . '.' . uniqid('', true) . '.tmp';

        file_put_contents($tmp, serialize(['value' => $value, 'expiry' => $expiry]));
        rename($tmp, $path);
    }

    private function removeDirectory(string $dir): void
    {
        foreach (glob($dir . '/*.cache') ?: [] as $path) {
            @unlink($path);
        }

        @rmdir($dir);
    }

    private function directory(string $group): string
    {
        $group = preg_replace('/[^A-Za-z0-9_-]/', '_', $group) ?: 'default';

        return rtrim($this->directory, '/\\') . '/' . $group;
    }

    private function path(string $key, string $group): string
    {
        return $this->directory($group) . '/' . sha1($key) . '.cache';
    }
}
