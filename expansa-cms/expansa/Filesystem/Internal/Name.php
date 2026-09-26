<?php

declare(strict_types=1);

namespace Expansa\Filesystem\Internal;

/**
 * Safe file names for uploads and renames.
 *
 * @internal
 * @package Expansa\Filesystem\Internal
 */
final class Name
{
    /**
     * Lowercase the name, transliterate Latin-1 letters and replace other symbols and spaces with dashes.
     *
     * @param string $name Name without the extension.
     * @return string
     */
    public static function sanitize(string $name): string
    {
        $from = mb_convert_encoding(
            'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:.,\\\'<>°ºª',
            'ISO-8859-1',
            'UTF-8'
        );
        $to   = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyrr                                 ';
        $name = mb_convert_encoding(mb_strtolower($name), 'ISO-8859-1', 'UTF-8');

        return trim((string) preg_replace('/[\s-]+/', '-', trim(strtr($name, $from, $to))), '-');
    }

    /**
     * Get a free path in the directory: `photo.jpg`, then `photo-2.jpg`, `photo-3.jpg`.
     *
     * @param string $directory
     * @param string $basename Name with the extension.
     * @return string
     */
    public static function unique(string $directory, string $basename): string
    {
        $path = $directory . '/' . $basename;
        if (! file_exists($path)) {
            return $path;
        }

        $filename  = pathinfo($basename, PATHINFO_FILENAME);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        $extension = $extension === '' ? '' : '.' . $extension;

        $suffix = 2;
        while (file_exists($path = $directory . '/' . $filename . '-' . $suffix . $extension)) {
            $suffix++;
        }

        return $path;
    }
}
