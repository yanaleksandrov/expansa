<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Option;
use Expansa\Ai\Enums\Status;
use Expansa\Facades\Db;
use Expansa\Facades\Debug;
use Expansa\Facades\Extensions;
use Expansa\Support\Is;
use Expansa\Support\Url;
use Throwable;

/**
 * Checks of the server, security, database, storage, background tasks, mail, extensions and the AI service
 * for the site-health page. Nothing is requested from other servers: only "Private files" requests the site itself.
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
     * Extensions some features use: plugin archives, upload types, photo metadata, translations, encryption.
     */
    private const array RECOMMENDED_EXTENSIONS = ['zip', 'fileinfo', 'exif', 'intl', 'sodium'];

    /**
     * Functions the CMS calls that hosting often lists in disable_functions.
     */
    private const array FUNCTIONS = ['curl_exec', 'popen', 'exec', 'proc_open', 'disk_free_space', 'opcache_get_status'];

    /**
     * File the scheduler touches on every run, see markScheduler().
     */
    private const string SCHEDULER_MARK = 'scheduler.last';

    /**
     * Minutes without a scheduler run after which cron counts as not working.
     */
    private const int SCHEDULER_DELAY = 10;

    /**
     * Logs larger than this are worth a look: something fails on every request.
     */
    private const int LOGS_SIZE = 100 * 1048576;

    /**
     * Directories that must not be served, probed with a temporary file.
     */
    private const array PRIVATE = ['storage/', 'cache/views/'];

    /**
     * Code directories and files a web server should not be able to change.
     */
    private const array CODE = ['expansa/', 'app/', 'dashboard/', 'bootstrap.php', 'index.php'];

    /**
     * Seconds a request of the site to itself may take; the probes run in parallel.
     */
    private const int PROBE_TIMEOUT = 3;

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
            ['id' => 'tasks', 'title' => t('Background Tasks'), 'icon' => 'ph ph-clock-countdown', 'checks' => self::tasks()],
            ['id' => 'mail', 'title' => t('Mail'), 'icon' => 'ph ph-envelope-simple', 'checks' => self::mail()],
            ['id' => 'extensions', 'title' => t('Plugins and Themes'), 'icon' => 'ph ph-plug', 'checks' => self::extensions()],
            ['id' => 'ai', 'title' => t('AI Assistant'), 'icon' => 'ph ph-sparkle', 'checks' => self::ai()],
        ];
    }

    /**
     * Records a scheduler run; the "schedule" hook calls it, the check reads the file time.
     */
    public static function markScheduler(): void
    {
        @touch(EX_STORAGE . self::SCHEDULER_MARK);
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
     * PHP version, extensions, limits and disabled functions.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function server(): array
    {
        $memory    = self::bytes((string) ini_get('memory_limit'));
        $missing   = array_values(array_filter(self::EXTENSIONS, fn (string $extension) => ! extension_loaded($extension)));
        $optional  = array_values(array_filter(self::RECOMMENDED_EXTENSIONS, fn (string $extension) => ! extension_loaded($extension)));
        $disabled  = array_values(array_filter(self::FUNCTIONS, fn (string $function) => ! function_exists($function)));
        // opcache.restrict_api makes the status a warning, which stops the request in debug mode
        $opcache   = function_exists('opcache_get_status') && (@opcache_get_status(false)['opcache_enabled'] ?? false);
        $upload    = min(self::bytes((string) ini_get('upload_max_filesize')), self::bytes((string) ini_get('post_max_size')));
        $execution = (int) ini_get('max_execution_time');

        return [
            self::check(
                t('PHP Version'),
                escape(PHP_VERSION),
                Requirements::php(),
                t('Expansa needs PHP %s or newer.', EX_REQUIRED_PHP_VERSION),
            ),
            self::check(
                t('PHP Extensions'),
                $missing === [] ? t('All Required') : t('Missing: %s', escape(implode(', ', $missing))),
                $missing === [],
                t('Ask your hosting provider to enable the missing extensions.'),
            ),
            self::check(
                t('Recommended Extensions'),
                $optional === [] ? t('All Installed') : t('Missing: %s', escape(implode(', ', $optional))),
                $optional === [],
                t('zip installs plugins from archives, fileinfo checks upload types, exif reads photo orientation, intl formats dates and numbers, sodium encrypts.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Disabled Functions'),
                $disabled === [] ? t('None') : escape(implode(', ', $disabled)),
                $disabled === [],
                in_array('curl_exec', $disabled, true)
                    ? t('Without curl_exec, the AI assistant and other requests to services do not work.')
                    : t('The CMS works around them, but background workers or disk checks are limited.'),
                in_array('curl_exec', $disabled, true) ? self::CRITICAL : self::RECOMMENDED,
            ),
            self::check(
                t('Memory Limit'),
                escape((string) ini_get('memory_limit')),
                $memory < 0 || $memory >= EX_REQUIRED_MEMORY * 1048576,
                t('Set memory_limit to at least %sM in php.ini.', EX_REQUIRED_MEMORY),
            ),
            self::check(
                t('Execution Time'),
                $execution === 0 ? t('No Limit') : t('%s s', $execution),
                $execution === 0 || $execution >= 30,
                t('Imports, updates and image processing need max_execution_time of at least 30 seconds.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Upload Size'),
                self::size($upload),
                $upload < 0 || $upload >= 8 * 1048576,
                t('Raise upload_max_filesize and post_max_size to upload larger files.'),
                self::RECOMMENDED,
            ),
            self::check(
                'OPcache',
                $opcache ? t('Enabled') : t('Disabled'),
                $opcache,
                t('OPcache keeps compiled PHP in memory and makes every page faster.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Architecture'),
                PHP_INT_SIZE === 8 ? t('64-bit') : t('32-bit'),
                PHP_INT_SIZE === 8,
                t('32-bit PHP cannot hold dates after 2038 and file sizes over 2 GB.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * HTTPS, debug output, secret keys, administrators and protected files.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function security(): array
    {
        $keys = defined('EX_KEYS') ? EX_KEYS : [];
        // the installer replaces the placeholders of env.example.php with random keys
        $isGenerated = $keys !== [] && array_all($keys, fn (string $key, string $name) => strlen($key) >= 32 && $key !== "$name.key");
        $isDisplayed = Debug::hasDetails();
        $isExposed   = filter_var(ini_get('expose_php'), FILTER_VALIDATE_BOOL);
        $exposed     = self::exposed();
        $writable    = array_values(array_filter(self::CODE, fn (string $path) => is_writable(EX_PATH . $path)));
        [$siteUrl, $requestUrl] = self::urls();

        try {
            // roles are stored as a JSON list, e.g. ["admin"]
            $admins = Db::count('users', ['roles[~]' => '"admin"']);
        } catch (Throwable) {
            $admins = null;
        }

        return [
            self::check(
                'HTTPS',
                Is::ssl() ? t('Enabled') : t('Disabled'),
                Is::ssl(),
                t('Without HTTPS, passwords and cookies travel unencrypted. Install a certificate.'),
            ),
            self::check(
                t('Administrators'),
                $admins === null ? t('Unknown') : (string) $admins,
                $admins === null || $admins > 0,
                t('No user has the admin role: settings, plugins and users cannot be managed. Assign the role in the database.'),
            ),
            self::check(
                t('Debug Mode'),
                Is::debug() ? t('Enabled') : t('Disabled'),
                ! Is::debug(),
                t('Debug mode slows the site down and stops pages on PHP warnings. Disable it on a public site.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Error Details'),
                $isDisplayed ? t('Shown to Visitors') : t('Hidden'),
                ! $isDisplayed,
                t('Turn off enabled or display in EX_DEBUG once the error is found.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Secret Keys'),
                $isGenerated ? t('Generated') : t('Default Values'),
                $isGenerated,
                t('Replace the keys in env.php with random values of 64 characters.'),
            ),
            self::check(
                t('Configuration File'),
                is_writable(EX_PATH . 'env.php') ? t('Writable') : t('Read-Only'),
                ! is_writable(EX_PATH . 'env.php'),
                t('Make env.php read-only, so a vulnerable plugin cannot change it.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Private Files'),
                match (true) {
                    $exposed === null => t('Could Not Check'),
                    $exposed === []   => t('Closed to the Web'),
                    default           => t('Open: %s', escape(implode(', ', $exposed))),
                },
                $exposed === [],
                $exposed === null
                    ? t('The site did not answer its own request, e.g. a firewall blocks it. Open /storage/logs/ in a browser: it must not show files.')
                    : t('Logs, AI tasks or the PHP source can be downloaded. On nginx add: %s', escape('location ~ ^/(storage|cache/views)/ { deny all; }')),
                $exposed === null ? self::RECOMMENDED : self::CRITICAL,
            ),
            self::check(
                t('Code Changes'),
                $writable === [] ? t('Read-Only') : t('Writable: %s', escape(implode(', ', $writable))),
                $writable === [],
                t('A vulnerable plugin could change the core. Leave writing to storage/, cache/ and plugins/ only.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Site Address'),
                escape($siteUrl ?: $requestUrl),
                $siteUrl === '' || $siteUrl === $requestUrl,
                t('The site address in the settings is %s, but the site is open at %s: links, redirects and cookies use the first one.', escape($siteUrl), escape($requestUrl)),
                self::RECOMMENDED,
            ),
            self::check(
                t('PHP Version Header'),
                $isExposed ? t('Sent') : t('Hidden'),
                ! $isExposed,
                t('Set expose_php = Off in php.ini, so responses do not tell the PHP version.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * Connection, server version, table prefix, encoding and size; a failed connection is the only check then.
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

        $charset = defined('EX_DB') ? (string) (EX_DB['charset'] ?? '') : '';

        try {
            $size = (int) Db::query(
                'SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = :database',
                [':database' => EX_DB['database']],
            )?->fetchColumn();
        } catch (Throwable) {
            $size = 0;
        }

        return [
            self::check(t('Connection'), t('Established'), true, ''),
            self::check(
                t('Server Version'),
                escape($version),
                Requirements::database($version),
                t('Expansa needs MySQL %s or newer.', EX_REQUIRED_MYSQL_VERSION),
            ),
            self::check(
                t('Encoding'),
                escape($charset),
                $charset === 'utf8mb4',
                t('Use utf8mb4: utf8 cannot store emoji and some characters of Asian languages.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Table Prefix'),
                defined('EX_DB') ? escape(EX_DB['prefix']) : '',
                defined('EX_DB') && EX_DB['prefix'] !== '',
                t('A prefix keeps the tables apart from other applications in the same database.'),
                self::RECOMMENDED,
            ),
            self::check(t('Size'), $size > 0 ? self::size($size) : t('Unknown'), true, ''),
        ];
    }

    /**
     * Writable directories, free disk space and the size of the logs.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function storage(): array
    {
        // hosting often lists it in disable_functions, and calling a disabled function is an Error
        $free = function_exists('disk_free_space') ? @disk_free_space(EX_PATH) : false;
        $logs = array_sum(array_map(filesize(...), glob(EX_STORAGE . 'logs/*.log') ?: []));

        $cache   = defined('EX_CACHE') ? EX_CACHE : [];
        $default = (string) ($cache['default'] ?? 'memory');
        $driver  = (string) ($cache['stores'][$default]['driver'] ?? $default);
        $better  = match (true) {
            extension_loaded('apcu')  => 'apcu',
            extension_loaded('redis') => 'redis',
            default                   => 'file',
        };

        return [
            self::check(
                t('Storage Directory'),
                is_writable(EX_STORAGE) ? t('Writable') : t('Not Writable'),
                is_writable(EX_STORAGE),
                t('Logs, tasks and uploads are written to %s.', 'storage/'),
            ),
            self::check(
                t('Cache Directory'),
                is_writable(EX_PATH . 'cache') ? t('Writable') : t('Not Writable'),
                is_writable(EX_PATH . 'cache'),
                t('Compiled views are written to %s.', 'cache/'),
            ),
            self::check(
                t('Free Disk Space'),
                $free === false ? t('Unknown') : self::size((int) $free),
                $free === false || $free >= 1073741824,
                t('Less than 1 GB is left: backups and uploads may fail.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Cache Store'),
                escape($driver),
                $driver !== 'memory',
                t('The memory store forgets everything after each request. Set EX_CACHE_STORE=%s or "default" of EX_CACHE.', $better),
                self::RECOMMENDED,
            ),
            self::check(
                t('Log Size'),
                self::size($logs),
                $logs < self::LOGS_SIZE,
                t('The logs are large: an error probably repeats on every request. Look at storage/logs.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * Cron runs of the scheduler, background workers and stuck AI tasks.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function tasks(): array
    {
        $mark   = @filemtime(EX_STORAGE . self::SCHEDULER_MARK);
        $ago    = $mark === false ? null : intdiv(time() - $mark, 60);
        // Work::createLauncher() starts the worker with popen() on Windows and exec() elsewhere
        $start  = PHP_OS_FAMILY === 'Windows' ? 'popen' : 'exec';
        $php    = Ai::getPhp();
        $stuck  = 0;
        $queued = 0;

        if (is_dir(EX_STORAGE . 'ai')) {
            try {
                $queue = Ai::queue();
                foreach ($queue->store->all() as $task) {
                    $stuck  += (int) ($task->status === Status::Running && $task->updatedAt < time() - $queue->timeout);
                    $queued += (int) ($task->status === Status::Queued && $task->updatedAt < time() - 300);
                }
            } catch (Throwable) {
                // a damaged task file is not a reason to break the page
            }
        }

        return [
            self::check(
                t('Scheduler'),
                $ago === null ? t('Never Ran') : t('%s min ago', $ago),
                $ago !== null && $ago <= self::SCHEDULER_DELAY,
                // the asterisks of the cron line would become emphasis in the Markdown of t()
                t('Add a cron job running every minute: %s', str_replace('*', '&#42;', escape('* * * * * php ' . EX_PATH . 'artisan schedule:run'))),
            ),
            self::check(
                t('Background Start'),
                function_exists($start) ? t('Available') : t('%s Is Disabled', $start),
                function_exists($start),
                t('Without %s, a request cannot start its worker at once: tasks wait for the next scheduler run.', $start),
                self::RECOMMENDED,
            ),
            self::check(
                t('PHP for the Console'),
                escape($php),
                ! str_contains(strtolower(basename($php)), 'fpm') && ! str_contains(strtolower(basename($php)), 'cgi'),
                t('This is the web server PHP; it cannot run console commands. Set the path of the PHP CLI in the "php" of EX_AI.'),
            ),
            self::check(
                t('Waiting Tasks'),
                (string) $queued,
                $queued === 0,
                t('Tasks have waited for a worker for more than 5 minutes: check the scheduler and the background start.'),
            ),
            self::check(
                t('Stuck Tasks'),
                (string) $stuck,
                $stuck === 0,
                t('A worker stopped without finishing; the scheduler retries such tasks.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * SMTP server, sender address and DKIM signing.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function mail(): array
    {
        $mail   = (array) Option::get('mail', []);
        $host   = (string) ($mail['host'] ?? '');
        $isSmtp = $host !== '';
        $from   = (string) ($mail['from'] ?? '');
        $dkim   = (array) ($mail['dkim'] ?? []);
        $isDkim = ($dkim['selector'] ?? '') !== '' && ($dkim['private'] ?? '') !== '';

        return [
            self::check(
                t('SMTP Server'),
                $isSmtp ? escape($host) : t('Not Set'),
                $isSmtp,
                t('Without SMTP, mail goes through PHP mail() and often lands in spam. Set it on the Mail tab of the settings.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Sender Address'),
                Is::email($from) ? escape($from) : t('Not Set'),
                Is::email($from),
                t('Set the sender on the Mail tab of the settings to an address of the site domain.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('DKIM Signature'),
                $isDkim ? escape((string) ($dkim['domain'] ?? '') ?: t('Enabled')) : t('Not Set'),
                $isDkim,
                t('A DKIM signature proves the mail comes from your domain. Set it on the Mail tab of the settings.'),
                self::RECOMMENDED,
            ),
        ];
    }

    /**
     * Active plugins and themes, and the plugins in quarantine.
     *
     * @return array<int, array{label: string, value: string, status: string, hint: string}>
     */
    private static function extensions(): array
    {
        $quarantined = Extensions::getQuarantined();
        $themes      = count(Extensions::get('theme'));

        return [
            self::check(
                t('Active Plugins'),
                (string) count(Extensions::get('plugin')),
                true,
                '',
            ),
            self::check(
                t('Active Theme'),
                $themes > 0 ? t('Active') : t('None'),
                $themes > 0,
                t('Without a theme, the public site shows only the default page. Activate a theme.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Plugins in Quarantine'),
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
                $isConfigured ? escape(Ai::getModel()) : t('Not Configured'),
                $isConfigured,
                t('Set the service on the AI tab of the settings to generate plugins in the chat.'),
                self::RECOMMENDED,
            ),
            self::check(
                t('Task Directory'),
                is_dir(EX_STORAGE . 'ai') ? t('Created') : t('Created on the First Task'),
                ! is_dir(EX_STORAGE . 'ai') || is_writable(EX_STORAGE . 'ai'),
                t('The background worker saves the tasks to %s.', 'storage/ai/'),
            ),
        ];
    }

    /**
     * Requests temporary files of the private directories and the PHP source through the site's own URL.
     * Returns the open paths, or null when the site did not answer any request.
     *
     * @return string[]|null
     */
    private static function exposed(): ?array
    {
        $token = bin2hex(random_bytes(8));
        $files = [];
        foreach (self::PRIVATE as $directory) {
            $path = EX_PATH . $directory . "health-$token.txt";
            if (is_dir(EX_PATH . $directory) && @file_put_contents($path, $token) !== false) {
                $files[$directory] = $path;
            }
        }

        try {
            $base = rtrim(Url::site(), '/') . '/';
            $urls = array_map(fn (string $path) => $base . substr($path, strlen(EX_PATH)), $files);
            // a PHP file served as text gives away the code and the settings it defines
            $urls['env.example.php'] = $base . 'env.example.php';

            $responses = self::fetch($urls);
            if (array_all($responses, fn (array $response) => $response['status'] === 0)) {
                return null;
            }

            $exposed = [];
            foreach ($responses as $name => $response) {
                $isOpen = $name === 'env.example.php'
                    ? str_contains($response['body'], "define('EX_DB'")
                    : $response['status'] === 200 && trim($response['body']) === $token;
                if ($isOpen) {
                    $exposed[] = $name;
                }
            }

            return $exposed;
        } finally {
            array_map(fn (string $path) => @unlink($path), $files);
        }
    }

    /**
     * Requests the URLs at once, without following redirects.
     *
     * @param array<string, string> $urls Names mapped to URLs
     * @return array<string, array{status: int, body: string}> Status 0 when there was no answer
     */
    private static function fetch(array $urls): array
    {
        if (! function_exists('curl_multi_init')) {
            return array_map(fn () => ['status' => 0, 'body' => ''], $urls);
        }

        $multi   = curl_multi_init();
        $handles = [];
        foreach ($urls as $name => $url) {
            $handles[$name] = curl_init($url);
            curl_setopt_array($handles[$name], [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::PROBE_TIMEOUT,
                CURLOPT_TIMEOUT        => self::PROBE_TIMEOUT,
                CURLOPT_SSL_OPTIONS    => CURLSSLOPT_NATIVE_CA,
            ]);
            curl_multi_add_handle($multi, $handles[$name]);
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi, 0.2);
            }
        } while ($running && $status === CURLM_OK);

        $responses = [];
        foreach ($handles as $name => $handle) {
            $responses[$name] = ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => (string) curl_multi_getcontent($handle)];
            curl_multi_remove_handle($multi, $handle);
        }
        curl_multi_close($multi);

        return $responses;
    }

    /**
     * The site address from the settings and the one the current request came to: scheme, host and port.
     *
     * @return array{string, string} The settings address, empty when not set, and the request one
     */
    private static function urls(): array
    {
        $https   = Is::ssl() || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $request = ($https ? 'https://' : 'http://') . strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        try {
            $setting = (string) Option::get('site.url', '');
        } catch (Throwable) {
            $setting = '';
        }

        $parts = parse_url($setting);
        $site  = isset($parts['scheme'], $parts['host'])
            ? strtolower($parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''))
            : '';

        return [$site, $request];
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
            $bytes < 0           => t('No Limit'),
            $bytes >= 1073741824 => t('%s GB', number_format($bytes / 1073741824, 1)),
            $bytes >= 1048576    => t('%s MB', number_format($bytes / 1048576)),
            default              => t('%s KB', number_format($bytes / 1024)),
        };
    }
}
