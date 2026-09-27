<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Closure;
use Error;
use ErrorException;
use Expansa\Extensions\Contracts\Extension;
use Expansa\Extensions\Exceptions\MissingProperty;
use Throwable;

/**
 * Loads plugins and themes by id and calls their lifecycle methods, the Extensions facade instance.
 *
 * A failing extension never takes the site down:
 * - an exception while loading or in a lifecycle method drops the extension for the current request;
 * - an `Error` (a bug: TypeError, undefined function) and a fatal error (redeclaration, memory, timeout)
 *   also quarantine it: the id goes to the quarantine file and the extension is skipped until `forget()`.
 * Fatal errors can not be caught, so the request they happen in still fails; the following ones work.
 *
 * @package Expansa\Extensions
 */
final class Manager
{
    /**
     * Error types that stop the script and reach only a shutdown function.
     */
    private const int FATAL = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;

    /**
     * Loaded extensions by type: `plugin`, `theme`.
     *
     * @var array<string, Extension[]>
     */
    private static array $extensions = [];

    /**
     * Directory the extension ids are relative to, with a trailing slash.
     */
    private static string $root = '';

    /**
     * JSON file of quarantined extensions; empty keeps failures for the current request only.
     */
    private static string $quarantine = '';

    /**
     * Receives every failure, e.g. to log it.
     *
     * @var (Closure(string, Throwable): void)|null
     */
    private static ?Closure $failed = null;

    /**
     * Id of the extension whose code runs now, to blame fatal errors raised in core files.
     */
    private static ?string $current = null;

    /**
     * Quarantine read in this request.
     *
     * @var array<string, array{error: string, file: string, line: int, time: int}>|null
     */
    private static ?array $quarantined = null;

    /**
     * Sets the extensions root, the quarantine file, and the failure callback.
     *
     * @param string $root Directory that holds the "plugins" and "themes" folders
     * @param string $quarantine JSON file of quarantined extensions, created on the first failure
     * @param (Closure(string, Throwable): void)|null $failed Receives the extension id and its error
     */
    public function configure(string $root, string $quarantine = '', ?Closure $failed = null): void
    {
        self::$root = rtrim($root, '/\\') . '/';
        self::$quarantine = $quarantine;
        self::$failed = $failed;
        self::$quarantined = null;
    }

    /**
     * Get the loaded extensions of a type.
     *
     * @param string $type `plugin` or `theme`.
     * @return Extension[]
     */
    public function get(string $type): array
    {
        return self::$extensions[$type] ?? [];
    }

    /**
     * Returns the quarantined extensions with the error that put each one there.
     *
     * @return array<string, array{error: string, file: string, line: int, time: int}> Id mapped to the error
     */
    public function getQuarantined(): array
    {
        return self::$quarantined ??= $this->read();
    }

    /**
     * Releases an extension from quarantine, e.g. after the admin fixed or reinstalled it.
     *
     * @param string $id Extension id like "plugins/seo"
     * @return bool Whether the extension was quarantined
     */
    public function forget(string $id): bool
    {
        $quarantined = $this->read();
        if (! isset($quarantined[$id])) {
            return false;
        }

        unset($quarantined[$id]);
        $this->write($quarantined);

        return true;
    }

    /**
     * Quarantines the extension an uncaught error comes from, for the application's error handler.
     * Only `Error` counts: exceptions are usually environment failures (database, network) and pass.
     *
     * @param Throwable $error Uncaught error of the request
     * @return string|null Quarantined extension id, or null when the error is not an extension bug
     */
    public function quarantine(Throwable $error): ?string
    {
        if (! $error instanceof Error) {
            return null;
        }

        foreach ([$error->getFile(), ...array_column($error->getTrace(), 'file')] as $file) {
            $id = $this->owner($file);
            if ($id !== null) {
                $this->isolate($id, $error);

                return $id;
            }
        }

        return null;
    }

    /**
     * Load the extensions by id, e.g. "plugins/seo"; ids other than "plugins/{dir}" or "themes/{dir}" are ignored.
     * Quarantined extensions are skipped; one that fails to load is dropped and reported.
     *
     * @param string[] $ids Extension ids
     */
    public function load(array $ids): void
    {
        $this->watch();

        $quarantined = $this->getQuarantined();
        foreach ($this->paths($ids) as $id => $path) {
            if (isset($quarantined[$id]) || ! is_file($path)) {
                continue;
            }

            $extension = null;
            $loaded = $this->guard($id, function () use ($path, &$extension): void {
                $extension = require_once $path;
                if ($extension instanceof Extension) {
                    $this->validate($extension);
                }
            });
            if (! $loaded || ! $extension instanceof Extension) {
                continue;
            }

            $extension->id   = $id;
            $extension->path = $path;

            self::$extensions[$extension->type][] = $extension;
        }
    }

    /**
     * Call register() of every loaded extension of the type.
     *
     * @param string $type `plugin` or `theme`
     */
    public function register(string $type): void
    {
        $this->call($type, 'register');
    }

    /**
     * Call boot() of every loaded extension of the type.
     *
     * @param string $type `plugin` or `theme`
     */
    public function boot(string $type): void
    {
        $this->call($type, 'boot');
    }

    /**
     * Call activate() of every loaded extension of the type.
     *
     * @param string $type `plugin` or `theme`
     */
    public function activate(string $type): void
    {
        $this->call($type, 'activate');
    }

    /**
     * Call deactivate() of every loaded extension of the type.
     *
     * @param string $type `plugin` or `theme`
     */
    public function deactivate(string $type): void
    {
        $this->call($type, 'deactivate');
    }

    /**
     * Call install() of every loaded extension of the type.
     *
     * @param string $type `plugin` or `theme`
     */
    public function install(string $type): void
    {
        $this->call($type, 'install');
    }

    /**
     * Call uninstall() of every loaded extension of the type.
     *
     * @param string $type `plugin` or `theme`
     */
    public function uninstall(string $type): void
    {
        $this->call($type, 'uninstall');
    }

    /**
     * Calls a lifecycle method of every loaded extension and drops the ones that fail.
     *
     * @param string $type `plugin` or `theme`
     * @param string $method Lifecycle method name
     */
    private function call(string $type, string $method): void
    {
        foreach (self::$extensions[$type] ?? [] as $i => $extension) {
            if (! $this->guard($extension->id, $extension->$method(...))) {
                unset(self::$extensions[$type][$i]);
            }
        }

        if (isset(self::$extensions[$type])) {
            self::$extensions[$type] = array_values(self::$extensions[$type]);
        }
    }

    /**
     * Runs extension code: an exception is reported, an `Error` also quarantines the extension.
     *
     * @param string $id Extension id
     * @param Closure(): void $callback Extension code
     * @return bool Whether the code finished without throwing
     */
    private function guard(string $id, Closure $callback): bool
    {
        self::$current = $id;
        try {
            $callback();

            return true;
        } catch (Error $error) {
            $this->isolate($id, $error);
        } catch (Throwable $error) {
            $this->report($id, $error);
        } finally {
            self::$current = null;
        }

        return false;
    }

    /**
     * Registers the shutdown function that quarantines the extension behind a fatal error, once per process.
     */
    private function watch(): void
    {
        // a shutdown function can not be unregistered, so the flag is never reset
        static $watching = false;
        if ($watching) {
            return;
        }
        $watching = true;

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error === null || ($error['type'] & self::FATAL) === 0) {
                return;
            }

            $id = $this->owner($error['file']) ?? self::$current;
            if ($id !== null) {
                $this->isolate($id, new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    /**
     * Finds the extension a file belongs to.
     *
     * @param string $file Absolute file path
     * @return string|null Extension id like "plugins/seo", or null for files outside extensions
     */
    private function owner(string $file): ?string
    {
        $root = str_replace('\\', '/', self::$root);
        $file = str_replace('\\', '/', $file);
        if ($root === '/' || strncasecmp($file, $root, strlen($root)) !== 0) {
            return null;
        }

        return preg_match('#^(?:plugins|themes)/[a-z0-9_-]+#i', substr($file, strlen($root)), $match) ? $match[0] : null;
    }

    /**
     * Reports a failure and adds the extension to the quarantine file.
     *
     * @param string $id Extension id
     * @param Throwable $error Failure
     */
    private function isolate(string $id, Throwable $error): void
    {
        $this->report($id, $error);
        if (self::$quarantine === '') {
            return;
        }

        // another request may have changed the file since this one read it
        $quarantined = $this->read();
        $quarantined[$id] = [
            'error' => $error->getMessage(),
            'file'  => $error->getFile(),
            'line'  => $error->getLine(),
            'time'  => time(),
        ];
        $this->write($quarantined);
    }

    /**
     * Passes a failure to the configured callback.
     *
     * @param string $id Extension id
     * @param Throwable $error Failure
     */
    private function report(string $id, Throwable $error): void
    {
        if (self::$failed !== null) {
            (self::$failed)($id, $error);
        }
    }

    /**
     * Reads the quarantine file; a missing or damaged file means an empty quarantine.
     *
     * @return array<string, array{error: string, file: string, line: int, time: int}>
     */
    private function read(): array
    {
        if (self::$quarantine === '' || ! is_file(self::$quarantine)) {
            return [];
        }

        $data = json_decode((string) file_get_contents(self::$quarantine), true);

        return is_array($data) ? $data : [];
    }

    /**
     * Writes the quarantine file and the request cache.
     *
     * @param array<string, array{error: string, file: string, line: int, time: int}> $quarantined Id mapped to the error
     */
    private function write(array $quarantined): void
    {
        self::$quarantined = $quarantined;
        if (self::$quarantine === '') {
            return;
        }

        if (! is_dir(dirname(self::$quarantine))) {
            mkdir(dirname(self::$quarantine), 0755, true);
        }
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
        file_put_contents(self::$quarantine, json_encode($quarantined, $flags), LOCK_EX);
    }

    /**
     * Check that an extension sets the required metadata.
     *
     * @param Extension $extension Loaded extension
     * @throws MissingProperty When name, description, or version is empty
     */
    private function validate(Extension $extension): void
    {
        foreach (['name', 'description', 'version'] as $property) {
            if (empty($extension->$property)) {
                throw new MissingProperty(sprintf('Extension property "%s" is required', $property));
            }
        }
    }

    /**
     * Entry files of extensions by id, e.g. "plugins/seo" => "{root}plugins/seo/index.php".
     * Ids other than "plugins/{dir}" or "themes/{dir}" are ignored, so a stored list can't point elsewhere.
     *
     * @param array<mixed> $ids Extension ids
     * @return array<string, string> Id mapped to its entry file
     */
    private function paths(array $ids): array
    {
        $paths = [];
        foreach ($ids as $id) {
            if (is_string($id) && preg_match('#^(plugins|themes)/[a-z0-9_-]+$#i', $id)) {
                $paths[$id] = self::$root . "$id/index.php";
            }
        }

        return $paths;
    }
}
