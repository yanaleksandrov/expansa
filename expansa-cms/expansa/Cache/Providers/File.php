<?php

declare(strict_types=1);

namespace Expansa\Cache\Providers;

use DateTime;
use Expansa\Cache\Contracts\Provider;
use Expansa\Cache\Traits\Locks;
use Expansa\Cache\Traits\Memoizes;
use Expansa\Cache\Traits\Serializes;

/**
 * Files on disk: one file per key, one directory per group. Needs only a writable directory
 * and outlives the process, at the cost of a filesystem call; the memo saves repeated reads.
 *
 * @package Expansa\Cache\Providers
 */
final class File implements Provider
{
    use Locks;
    use Memoizes;
    use Serializes;

    public function __construct(

        /**
         * Root directory, group subdirectories are created on first write.
         */
        private readonly string $directory,
    ) {}

    #[\Override]
    public function add(string $key, mixed $value, string $group = 'default', DateTime|string|null $expiry = null): bool
    {
        if ($this->isLocked($group, $key) || $this->hasMemoized($group, $key)) {
            return false;
        }

        $entry = $this->read($key, $group);
        if ($entry !== null) {
            $this->memoize($group, $key, $entry['value']);

            return false;
        }

        $expiry = is_string($expiry) ? new DateTime($expiry)->getTimestamp() : $expiry?->getTimestamp();
        if ($expiry !== null && $expiry <= time()) {
            return false;
        }

        $this->write($key, $group, $value, $expiry);
        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function set(string $key, mixed $value, string $group = 'default'): bool
    {
        $this->write($key, $group, $value, null);
        $this->memoize($group, $key, $value);

        return true;
    }

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

        if ($callback === null) {
            return null;
        }

        $value = $callback();
        $this->add($key, $value, $group);

        return $value;
    }

    #[\Override]
    public function pull(string $key, string $group = 'default'): mixed
    {
        $value = $this->get($key, $group);

        $this->forget($key, $group);

        return $value;
    }

    #[\Override]
    public function forget(string $key = '', string $group = 'default'): bool
    {
        if ($key !== '') {
            @unlink($this->path($key, $group));
            $this->forgetMemoized($group, $key);

            return true;
        }

        $directory = $this->directory($group);
        foreach (glob($directory . '/*.cache') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($directory);

        $this->forgetMemoizedGroup($group);

        return true;
    }

    /**
     * Keeps the expiry of the value.
     */
    #[\Override]
    public function increase(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        $entry = $this->read($key, $group);
        if ($entry === null || ! is_numeric($entry['value'])) {
            return false;
        }

        $value = $entry['value'] + $amount;

        $this->write($key, $group, $value, $entry['expiry']);
        $this->memoize($group, $key, $value);

        return true;
    }

    #[\Override]
    public function decrease(string $key, int|float $amount = 1, string $group = 'default'): bool
    {
        return $this->increase($key, -$amount, $group);
    }

    /**
     * Delete expired files of every group. Not called automatically: expired keys that are never
     * read stay on disk, run it from a scheduled job.
     *
     * @return void
     */
    public function purgeExpired(): void
    {
        foreach (glob(rtrim($this->directory, '/\\') . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            foreach (glob($directory . '/*.cache') ?: [] as $path) {
                $entry = $this->decode($path);

                if ($entry !== null && $entry['expiry'] !== null && $entry['expiry'] <= time()) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * Read an entry, an expired one is deleted.
     *
     * @param string $key
     * @param string $group
     * @return array{value: mixed, expiry: int|null}|null
     */
    private function read(string $key, string $group): ?array
    {
        $path = $this->path($key, $group);
        if (! is_file($path)) {
            return null;
        }

        $entry = $this->decode($path);
        if ($entry !== null && $entry['expiry'] !== null && $entry['expiry'] <= time()) {
            @unlink($path);

            return null;
        }

        return $entry;
    }

    /**
     * @param string $path
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
     * Write through a temporary file and rename, so a reader never sees a half-written file.
     *
     * @param string   $key
     * @param string   $group
     * @param mixed    $value
     * @param int|null $expiry
     * @return void
     */
    private function write(string $key, string $group, mixed $value, ?int $expiry): void
    {
        $directory = $this->directory($group);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $this->path($key, $group);
        $tmp  = $path . '.' . uniqid('', true) . '.tmp';

        file_put_contents($tmp, $this->serializeValue(['value' => $value, 'expiry' => $expiry]));
        rename($tmp, $path);
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
