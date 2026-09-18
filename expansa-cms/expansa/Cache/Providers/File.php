<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Concerns\Locks;
use Expansa\Cache\Concerns\Memoizes;
use Expansa\Cache\Concerns\Serializes;
use Expansa\Cache\Contracts\Provider;

/**
 * A cache provider backed by plain files on disk — one file per key, one directory per group.
 * Needs no extension or server, just a writable directory; survives past a single process like
 * Database/Redis/Memcached, at the cost of a filesystem round-trip per call.
 *
 * A request-local L1 memo (see Memoizes) sits in front of the filesystem: a key read twice in
 * the same request costs one file open, not two — this is the slowest backend per call, so it
 * benefits from the memo the most.
 */
class File implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    public function __construct(private readonly string $directory = EX_PATH . 'storage/cache/')
    {
    }

    /**
     * $expiry accepts an absolute DateTime or a relative time string (e.g. "+1 day").
     */
    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): mixed
    {
        if ($this->isLocked($group, $key)) {
            return false;
        }

        if ($this->hasMemoized($group, $key)) {
            return $this->memoized($group, $key);
        }

        if (is_string($expiry)) {
            $expiry = new DateTime($expiry);
        }

        $entry = $this->read($key, $group);

        if ($entry !== null) {
            return $this->memoize($group, $key, $entry['value']);
        }

        // An expiry already in the past is never actually stored (or memoized), matching every
        // other provider's behavior of an add()'d-then-immediately-expired entry never being
        // visible to a later get().
        if ($expiry === null || $expiry->getTimestamp() > time()) {
            $this->write($key, $group, $value, $expiry?->getTimestamp());

            return $this->memoize($group, $key, $value);
        }

        return $value;
    }

    /**
     * Sets a value in the cache for a given key and group. The value never expires — use add()
     * with an $expiry for a TTL-bound entry.
     */
    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): mixed
    {
        $this->write($key, $group, $value, null);

        return $this->memoize($group, $key, $value);
    }

    /**
     * Retrieves data from the cache, optionally populating it via $callback on a miss.
     */
    #[\Override]
    public function get(string $key, string $group = 'default', ?callable $callback = null): mixed
    {
        if ($this->hasMemoized($group, $key)) {
            return $this->memoized($group, $key);
        }

        $entry = $this->read($key, $group);

        if ($entry !== null) {
            return $this->memoize($group, $key, $entry['value']);
        }

        if ($callback !== null) {
            return $this->add($key, $callback(), $group);
        }

        return null;
    }

    /**
     * Retrieves and removes data from the cache.
     */
    #[\Override]
    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    /**
     * Clears data from the cache. An empty $key clears the whole group.
     */
    #[\Override]
    public function forget(string $key = '', string $group = 'default'): bool
    {
        if ($key !== '') {
            @unlink($this->path($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        $this->removeDirectory($this->directory($group));
        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Increases the value of a key by a given amount, preserving its expiry if any.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        if ($key === '') {
            return false;
        }

        $entry = $this->read($key, $group);

        if ($entry === null || ! is_numeric($entry['value'])) {
            return false;
        }

        $value = $entry['value'] + $amount;

        $this->write($key, $group, $value, $entry['expiry']);
        $this->memoize($group, $key, $value);

        return true;
    }

    /**
     * Decreases the value of a key by a given amount.
     */
    #[\Override]
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
                $entry = $this->decode($path);

                if ($entry !== null && $entry['expiry'] !== null && $entry['expiry'] <= time()) {
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

        $entry = $this->decode($path);

        if ($entry === null) {
            return null;
        }

        if ($entry['expiry'] !== null && $entry['expiry'] <= time()) {
            @unlink($path);

            return null;
        }

        return $entry;
    }

    /**
     * Reads and decodes a cache file's raw contents, without regard to expiry.
     *
     * @return array{value: mixed, expiry: int|null}|null
     */
    private function decode(string $path): ?array
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $entry = $this->unserializeValue($contents);

        return is_array($entry) && array_key_exists('value', $entry) ? $entry : null;
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

        file_put_contents($tmp, $this->serializeValue(['value' => $value, 'expiry' => $expiry]));
        rename($tmp, $path);
    }

    /**
     * Deletes every cache file in $dir along with the directory itself.
     */
    private function removeDirectory(string $dir): void
    {
        foreach (glob($dir . '/*.cache') ?: [] as $path) {
            @unlink($path);
        }

        @rmdir($dir);
    }

    /**
     * The on-disk directory for $group, sanitized to a safe path segment.
     */
    private function directory(string $group): string
    {
        $group = preg_replace('/[^A-Za-z0-9_-]/', '_', $group) ?: 'default';

        return rtrim($this->directory, '/\\') . '/' . $group;
    }

    /**
     * The on-disk file path for $key/$group. Hashed so an arbitrary key is always a safe
     * filename.
     */
    private function path(string $key, string $group): string
    {
        return $this->directory($group) . '/' . sha1($key) . '.cache';
    }
}
