<?php

declare(strict_types=1);

namespace Expansa\Support;

use FilesystemIterator;
use PhpToken;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Class map of autoload.php: every class, interface, trait and enum declared in the given directories
 * with its file, so the autoloader needs no file checks. Built by `php artisan autoload:dump`.
 *
 * @package Expansa\Support
 */
final class ClassMap
{
    /**
     * Directories of the framework and the app, relative to EX_PATH.
     */
    public const array DIRECTORIES = ['expansa', 'app'];

    /**
     * Find the declared classes of PHP files.
     *
     * @param string   $root        Directory the paths of the map are relative to, with the trailing slash.
     * @param string[] $directories Directories to scan, relative to $root.
     * @return array<string, string> Class name => file path relative to $root, sorted by the class name.
     */
    public static function scan(string $root, array $directories = self::DIRECTORIES): array
    {
        $classes = [];

        foreach ($directories as $directory) {
            if (! is_dir($root . $directory)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root . $directory, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root)));
                foreach (self::declarations((string) file_get_contents($file->getPathname())) as $class) {
                    $classes[$class] ??= $path;
                }
            }
        }

        ksort($classes);

        return $classes;
    }

    /**
     * Write the class map as a PHP file, replacing the old one at once.
     *
     * @param string                $file
     * @param array<string, string> $classes
     * @return void
     * @throws RuntimeException If the file can not be written.
     */
    public static function dump(string $file, array $classes): void
    {
        $temporary = $file . '.' . getmypid() . '.tmp';

        $written = file_put_contents($temporary, '<?php return ' . var_export($classes, true) . ";\n");

        if ($written === false || ! rename($temporary, $file)) {
            @unlink($temporary);

            throw new RuntimeException("Unable to write the class map $file");
        }

        // opcache may keep the previous map
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }
    }

    /**
     * Get the fully qualified names of the classes, interfaces, traits and enums declared in PHP code.
     *
     * @param string $code
     * @return string[]
     */
    private static function declarations(string $code): array
    {
        $tokens    = PhpToken::tokenize($code);
        $namespace = '';
        $classes   = [];

        foreach ($tokens as $i => $token) {
            if ($token->id === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; isset($tokens[$j]) && $tokens[$j]->text !== ';' && $tokens[$j]->text !== '{'; $j++) {
                    $namespace .= $tokens[$j]->isIgnorable() ? '' : $tokens[$j]->text;
                }

                continue;
            }

            if (! in_array($token->id, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            // Foo::class and anonymous classes (new class) are not declarations
            $p = $i - 1;
            while ($p >= 0 && $tokens[$p]->isIgnorable()) {
                $p--;
            }
            if ($p >= 0 && ($tokens[$p]->id === T_DOUBLE_COLON || $tokens[$p]->id === T_NEW)) {
                continue;
            }

            $j = $i + 1;
            while (isset($tokens[$j]) && $tokens[$j]->isIgnorable()) {
                $j++;
            }
            if (isset($tokens[$j]) && $tokens[$j]->id === T_STRING) {
                $classes[] = ltrim($namespace . '\\' . $tokens[$j]->text, '\\');
            }
        }

        return $classes;
    }
}
