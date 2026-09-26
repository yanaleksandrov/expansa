<?php

declare(strict_types=1);

namespace Expansa\Session\Internal;

use ArrayAccess;
use Expansa\Session\Contracts\Flash as FlashContract;

/**
 * Flash messages kept in the session data under one key, grouped by message key.
 *
 * @internal
 * @package Expansa\Session
 */
final class Flash implements FlashContract
{
    public function __construct(

        /**
         * Session data, bound by reference.
         *
         * @var array<string, mixed>|ArrayAccess<string, mixed>
         */
        private array|ArrayAccess &$storage,

        /**
         * Key of the session data that holds the messages.
         */
        private string $storageKey = '_flash',
    ) {}

    public function add(string $key, string $message): void
    {
        $this->storage[$this->storageKey][$key][] = $message;
    }

    public function pull(string $key): array
    {
        $messages = $this->storage[$this->storageKey][$key] ?? [];

        unset($this->storage[$this->storageKey][$key]);

        return $messages;
    }

    public function pullAll(): array
    {
        $messages = $this->storage[$this->storageKey] ?? [];

        $this->flush();

        return $messages;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$this->storageKey][$key]);
    }

    public function set(string $key, array $messages): void
    {
        $this->storage[$this->storageKey][$key] = $messages;
    }

    public function flush(): void
    {
        unset($this->storage[$this->storageKey]);
    }
}
