<?php

declare(strict_types=1);

namespace Expansa\Filesystem;

/**
 * Class Disk
 *
 * This class represents file system operations. It provides methods for working with files and directories.
 */
class Disk
{
    /**
     * Creates a File object to work with a file.
     *
     * @param string $filepath The path to the file.
     * @return File Returns an instance of the File class to work with the specified file.
     */
    public function file(string $filepath): File
    {
        return new File($filepath);
    }

    /**
     * Creates a Directory object to work with a directory.
     *
     * @param string $dirpath The path to the directory.
     * @return Directory Returns an instance of the Directory class to work with the specified directory.
     */
    public function dir(string $dirpath): Directory
    {
        return new Directory($dirpath);
    }
}
