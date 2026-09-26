<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Contracts;

use Expansa\Filesystem\Exceptions\OperationFailed;

interface Directory extends Entry
{
    /**
     * Change the permissions of the directory, with $recursive also of its contents.
     *
     * @param int  $mode      Mode of the directories, files inside get 0644.
     * @param bool $recursive
     * @return static
     * @throws OperationFailed
     */
    public function chmod(int $mode = 0755, bool $recursive = false): static;

    /**
     * Get the paths of the subdirectories, sorted.
     *
     * @param int $depth Levels of nesting below the first one.
     * @return string[]
     */
    public function directories(int $depth = 0): array;

    /**
     * Get the nested subdirectories as a tree: `['root' => ['child' => []]]`.
     *
     * @return array<string, array>
     */
    public function tree(): array;

    /**
     * Get the paths matching a glob pattern, sorted; directories end with a slash.
     *
     * @param string $pattern
     * @param int    $depth Levels of nesting below the first one.
     * @return string[]
     */
    public function files(string $pattern = '*', int $depth = 0): array;

    /**
     * Create the directory with its parents if it does not exist.
     *
     * @param int $mode
     * @return static
     * @throws OperationFailed
     */
    public function create(int $mode = 0755): static;
}
