<?php

declare(strict_types=1);

namespace App\Support;

use Expansa\Facades\Db;
use Expansa\Facades\Debug;
use Expansa\Facades\Extensions;
use Expansa\Support\Is;
use Throwable;

/**
 * Checks of the server, security, database, storage, extensions and the AI service for the site-health page.
 * Every check is cheap and local: nothing is requested from other servers.
 * Labels, values and hints are HTML: translations as t() returns them, other values escaped.
 */
final class SiteHealth
{
    public const string GOOD = 'good';

    public const string RECOMMENDED = 'recommended';

    public const string CRITICAL = 'critical';

    /**
     * Extensions the dashboard and the installer need.
     */
    private const array EXTENSIONS = ['pdo', 'curl', 'mbstring', 'gd', 'json', 'openssl'];

    /**
     * Runs the checks, grouped by section.
     *
     * @return array<int, array{id: string, title: string, icon: string, checks: array<int, array{label: string, value: string, status: string, hint: string}>}>
     */
    public static function groups(): array
    {
        return [
            ['id' => 'server', 'title' => t('Server'), 'icon' => 'ph ph-hard-drives', 'checks' => self::server()],
            ['id' => 'security', 'title' => t('Security'), 'icon' => 'ph ph-shield-check', 'checks' => self::security()],
            ['id' => 'database', 'title' => t('Database'), 'icon' => 'ph ph-database', 'checks' => self::database()],
            ['id' => 'storage', 'title' => t('Storage'), 'icon' => 'ph ph-folder-simple', 'checks' => self::storage()],
            ['id' => 'extensions', 'title' => t('Plugins and themes'), 'icon' => 'ph ph-plug', 'checks' => self::extensions()],
            ['id' => 'ai', 'title' => t('AI assistant'), 'icon' => 'ph ph-sparkle', 'checks' => self::ai()],
        ];
    }

    /**
     * Counts the checks of every status, worst status of a group first.
     *
     * @param array<int, array{checks: array<int, array{status: string}>}> $groups
     * @return array<string, int>
     */
    public static function count(array $groups): array
    {
        $count = [self::CRITICAL => 0, self::RECOMMENDED => 0, self::GOOD => 0];
        foreach ($groups as $group) {
            foreach ($group['checks'] as $check) {
                $count[$check['status']]++;
            }
        }

        return $count;
    }

    /**
     * The worst status of the checks.
     *
     * @param array<int, array{status: string}> $checks
     */
    public static function status(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        return match (true) {
            in_array(self::CRITICAL, $statuses, true)    => self::CRITICAL,
            in_array(self::RECOMMENDED, $statuses, true) => self::RECOMMENDED,
            default                                      => self::GOOD,
        };
    }

    /**
     * PHP version, extensions and limits.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function server(): array
    {
        $memory  = self::bytes((string) ini_get('memory_limit'));
        $missing = array_values(array_filter(self::EXTENSIONS, fn (string $extension) => ! extension_loaded($extension)));
        // opcache.restrict_api makes the status a warning, which stops the request in debug mode
        $opcache = function_exists('opcache_get_status') && (@opcache_get_status(false)['opcache_enabled'] ?? false);
        $upload  = min(self::bytes((string) ini_get('upload_max_filesize')), self::bytes((string) ini_get('post_max_size')));

        return [
            self::check(
                t('PHP version'),
                escape(PHP_VERSION),
                Requirements::php(),
                t('Expansa needs PHP %s or newer.', EX_REQUIRED_PHP_VERSION),
            ),
            self::check(
                t('PHP extensions'),
                $missing === [] ? t('All required') : t('Missing: %s', escape(implode(', ', $missing))),
                $missing === [],
                t('Ask your hosting provider to enable the missing extensions.'),
            ),
            self::check(
                t('Memory limit'),
                escape((string) ini_get('memory_limit')),
                $memory < 0 || $memory >= EX_REQUIRED_MEMORY * 1048576,
                t('Set memory_limit to at least %sM in php.ini.', EX_REQUIRED_MEMORY),
            ),
            self::check(
                t('Upload size'),
                self::size($upload),
                $upload < 0 || $upload >= 8 * 1048576,
                t('Raise upload_max_filesize and post_max_size to upload larger files.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('OPcache'),
                $opcache ? t('Enabled') : t('Disabled'),
                $opcache,
                t('OPcache keeps compiled PHP in memory and makes every page faster.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * HTTPS, debug output, secret keys and the configuration file.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function security(): array
    {
        $keys = defined('EX_KEYS') ? EX_KEYS : [];
        // the installer replaces the placeholders of env.example.php with random keys
        $isGenerated = $keys !== [] && array_all($keys, fn (string $key, string $name) => strlen($key) >= 32 && $key !== "$name.key");
        $isDisplayed = Debug::hasDetails();

        return [
            self::check(
                t('HTTPS'),
                Is::ssl() ? t('Enabled') : t('Disabled'),
                Is::ssl(),
                t('Without HTTPS passwords and cookies travel unencrypted. Install a certificate.'),
            ),
            self::check(
                t('Debug mode'),
                Is::debug() ? t('Enabled') : t('Disabled'),
                ! Is::debug(),
                t('Debug mode shows the code of errors to visitors. Disable it on a public site.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Error details'),
                $isDisplayed ? t('Shown to visitors') : t('Hidden'),
                ! $isDisplayed,
                t('Turn off enabled or display in EX_DEBUG once the error is found.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Secret keys'),
                $isGenerated ? t('Generated') : t('Default values'),
                $isGenerated,
                t('Replace the keys in env.php with random values of 64 characters.'),
            ),
            self::check(
                t('Configuration file'),
                is_writable(EX_PATH . 'env.php') ? t('Writable') : t('Read-only'),
                ! is_writable(EX_PATH . 'env.php'),
                t('Make env.php read-only, so a vulnerable plugin can not change it.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * Connection, server version and table prefix; a failed connection is the only check then.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function database(): array
    {
        try {
            $version = Db::version();
        } catch (Throwable $e) {
            return [self::check(t('Connection'), t('Failed'), false, escape($e->getMessage()))];
        }

        return [
            self::check(t('Connection'), t('Established'), true, ''),
            self::check(
                t('Server version'),
                escape($version),
                Requirements::database($version),
                t('Expansa needs MySQL %s or newer.', EX_REQUIRED_MYSQL_VERSION),
            ),
            self::check(
                t('Table prefix'),
                defined('EX_DB') ? escape(EX_DB['prefix']) : '',
                defined('EX_DB') && EX_DB['prefix'] !== '',
                t('A prefix keeps the tables apart from other applications in the same database.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * Writable directories and free disk space.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function storage(): array
    {
        // hosting often lists it in disable_functions, and calling a disabled function is an Error
        $free = function_exists('disk_free_space') ? @disk_free_space(EX_PATH) : false;

        return [
            self::check(
                t('Storage directory'),
                is_writable(EX_STORAGE) ? t('Writable') : t('Not writable'),
                is_writable(EX_STORAGE),
                t('Logs, tasks and uploads are written to %s.', 'storage/'),
            ),
            self::check(
                t('Cache directory'),
                is_writable(EX_PATH . 'cache') ? t('Writable') : t('Not writable'),
                is_writable(EX_PATH . 'cache'),
                t('Compiled views are written to %s.', 'cache/'),
            ),
            self::check(
                t('Free disk space'),
                $free === false ? t('Unknown') : self::size((int) $free),
                $free === false || $free >= 1073741824,
                t('Less than 1 GB is left: backups and uploads may fail.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * Active plugins and the ones in quarantine.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function extensions(): array
    {
        $quarantined = Extensions::getQuarantined();

        return [
            self::check(
                t('Active plugins'),
                (string) count(Extensions::get('plugin')),
                true,
                '',
            ),
            self::check(
                t('Plugins in quarantine'),
                $quarantined === [] ? t('None') : escape(implode(', ', array_keys($quarantined))),
                $quarantined === [],
                t('These plugins failed and were turned off. Fix or reinstall them, then release them.'),
            ),
        ];
    }

    /**
     * Service key and the task directory of the background worker.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function ai(): array
    {
        $isConfigured = Ai::isConfigured();

        return [
            self::check(
                t('Service'),
                $isConfigured ? escape((string) (EX_AI['model'] ?? '')) : t('Not configured'),
                $isConfigured,
                t('Add the service key to EX_AI in env.php to generate plugins in the chat.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Task directory'),
                is_dir(EX_STORAGE . 'ai') ? t('Created') : t('Created on the first task'),
                ! is_dir(EX_STORAGE . 'ai') || is_writable(EX_STORAGE . 'ai'),
                t('The background worker saves the tasks to %s.', 'storage/ai/'),
            ),
        ];
    }

    /**
     * Builds one check.
     *
     * @param string $label  What is checked
     * @param string $value  Current value
     * @param bool   $passes Whether the value is fine
     * @param string $hint   What to do when it is not
     * @param string $fail   Status of a failed check
     * @return array{label: string, value: string, status: string, hint: string}
     */
    private static function check(string $label, string $value, bool $passes, string $hint, string $fail = self::CRITICAL): array
    {
        return ['label' => $label, 'value' => $value, 'status' => $passes ? self::GOOD : $fail, 'hint' => $passes ? '' : $hint];
    }

    /**
     * Converts a php.ini size like "128M" to bytes; -1 means no limit.
     *
     * @param string $value php.ini size
     */
    private static function bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g'     => $number * 1073741824,
            'm'     => $number * 1048576,
            'k'     => $number * 1024,
            default => $number,
        };
    }

    /**
     * Formats a byte count; -1 means no limit.
     *
     * @param int $bytes Size in bytes
     */
    private static function size(int $bytes): string
    {
        return match (true) {
            $bytes < 0           => t('No limit'),
            $bytes >= 1073741824 => t('%s GB', number_format($bytes / 1073741824, 1)),
            $bytes >= 1048576    => t('%s MB', number_format($bytes / 1048576)),
            default              => t('%s KB', number_format($bytes / 1024)),
        };
    }
}
