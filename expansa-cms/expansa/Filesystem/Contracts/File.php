<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Contracts;

use Expansa\Filesystem\Exceptions\FilesystemException;

interface File extends Entry
{
    /**
     * Change the file permissions.
     *
     * @param int $mode
     * @return static
     * @throws FilesystemException
     */
    public function chmod(int $mode = 0644): static;

    /**
     * Replace substrings in the file: `['{{name}}' => 'shop']`.
     *
     * @param array<string, string> $pairs
     * @return static
     * @throws FilesystemException
     */
    public function replace(array $pairs): static;

    /**
     * Write to the file, creating it with its directory.
     *
     * @param string $content
     * @param bool   $append False to overwrite the file.
     * @return static
     * @throws FilesystemException
     */
    public function write(string $content, bool $append = true): static;

    /**
     * Get the file contents, an empty string for a missing file.
     *
     * @return string
     */
    public function read(): string;

    /**
     * Set the modification and access time.
     *
     * @param int|null $time  Modification time, now by default.
     * @param int|null $atime Access time, the modification time by default.
     * @return static
     * @throws FilesystemException
     */
    public function touch(?int $time = null, ?int $atime = null): static;
}
