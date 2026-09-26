<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Contracts;

use Expansa\Filesystem\Exceptions\FilesystemException;

/**
 * A file or a directory addressed by its path.
 *
 * @package Expansa\Filesystem\Contracts
 */
interface Entry
{
    /**
     * Full path without the trailing slash.
     */
    public string $path { get; }

    /**
     * Whether the entry exists and has the expected type.
     */
    public bool $exists { get; }

    /**
     * Size in bytes, the total size of the files inside for a directory.
     */
    public int $bytes { get; }

    /**
     * Delete the contents, the entry itself stays.
     *
     * @return static
     * @throws FilesystemException
     */
    public function clean(): static;

    /**
     * Copy the entry next to itself under a new name.
     *
     * @param string $name New name, a file keeps its extension.
     * @return static The copy.
     * @throws FilesystemException
     */
    public function copy(string $name): static;

    /**
     * Move the entry into a directory, keeping its name.
     *
     * @param string $directory
     * @return static
     * @throws FilesystemException
     */
    public function move(string $directory): static;

    /**
     * Rename the entry in its directory.
     *
     * @param string $name New name, a file keeps its extension.
     * @return static
     * @throws FilesystemException
     */
    public function rename(string $name): static;

    /**
     * Delete the entry with its contents.
     *
     * @return bool False if the entry does not exist or can not be deleted.
     */
    public function delete(): bool;

    /**
     * Send the entry to the browser and end the request, a directory as a zip archive.
     *
     * @return void
     */
    public function download(): void;
}
