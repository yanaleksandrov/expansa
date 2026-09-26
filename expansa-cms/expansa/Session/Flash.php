<?php

declare(strict_types=1);

namespace Expansa\Session;

use ArrayAccess;
use Expansa\Session\Contracts\FlashInterface;

/**
 * Flash messages.
 */
final class Flash implements FlashInterface
{
    public function __construct(

        /**
         * Session data, bound by reference.
         *
         * @var array<string, mixed>|ArrayAccess<string, mixed>
         */
        private array|ArrayAccess &$storage,

        /**
         * Storage key holding all flash messages, grouped by message key.
         */
        private string $storageKey = '_flash',
    ) {} // phpcs:ignore

    public function add(string $key, string $message): void
    {
        // Create array for this key
        if (!isset($this->storage[$this->storageKey][$key])) {
            $this->storage[$this->storageKey][$key] = [];
        }

        // Push onto the array
        $this->storage[$this->storageKey][$key][] = $message;
    }

    public function get(string $key): array
    {
        if (!$this->has($key)) {
            return [];
        }

        $return = $this->storage[$this->storageKey][$key];
        unset($this->storage[$this->storageKey][$key]);

        return (array) $return;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$this->storageKey][$key]);
    }

    public function clear(): void
    {
        unset($this->storage[$this->storageKey]);
    }

    public function set(string $key, array $messages): void
    {
        $this->storage[$this->storageKey][$key] = $messages;
    }

    public function all(): array
    {
        $result = $this->storage[$this->storageKey] ?? [];
        $this->clear();

        return (array) $result;
    }
}
