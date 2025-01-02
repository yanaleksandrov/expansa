<?php

declare(strict_types=1);

namespace Expansa\Disk;

/**
 * Trait that provides file-related properties.
 *
 * @property string $path       The full path to the file.
 * @property string $filename   The name of the file without its directory.
 * @property string $basename   The base name of the file (with extension).
 * @property string $dirname    The directory name containing the file.
 * @property string $dirpath    The full path to the directory containing the file.
 * @property string $hash       The signature or hash of the file.
 * @property string $mime       The MIME type of the file.
 * @property string $type       The general type of the file (e.g., 'image', 'document').
 * @property string $url        The URL to access the file.
 * @property string $extension  The file extension (e.g., 'jpg', 'txt').
 * @property string $modified   The last modified time of the file.
 * @property string $size       The human-readable size of the file (e.g., '1.2 MB').
 * @property int    $sizeB      The size of the file in bytes.
 * @property float  $sizeKb     The size of the file in kilobytes.
 * @property float  $sizeMb     The size of the file in megabytes.
 * @property float  $sizeGb     The size of the file in gigabytes.
 * @property int    $permission The file's permission (e.g., 0644).
 * @property bool   $exists     Indicates if the file exists.
 * @property array  $errors     An array of error messages related to the file.
 */
trait EntryTraits
{
    public string $path = '';

    public string $filename = '';

    public string $basename = '';

    public string $dirname = '';

    public string $dirpath = '';

    public string $hash = '';

    public string $mime = '';

    public string $type = '';

    public string $url = '';

    public string $extension = '';

    public string $modified = '';

    public string $size = '';

    public float $sizeKb = 0;

    public float $sizeMb = 0;

    public float $sizeGb = 0;

    public int $sizeB = 0;

    public int $permission = 0;

    public bool $exists = false;

    public array $errors = [];
}