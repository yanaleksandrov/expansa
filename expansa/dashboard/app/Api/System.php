<?php

declare(strict_types=1);

namespace dashboard\app\Api;

use app\Option;
use app\User;
use Expansa\Database\Connector\Manager;
use Expansa\Database\Db;
use Expansa\Disk;
use Expansa\Error;
use Expansa\Hook;
use Expansa\Safe;

class System
{
    /**
     * Endpoint name.
     */
    public string $endpoint = 'system';

    /**
     * Check the compliance of the server with the minimum requirements.
     *
     * @since 2025.1
     */
    public static function test(): array
    {
        $checks = [
            'connection' => false,
            'pdo'        => false,
            'curl'       => false,
            'mbstring'   => false,
            'gd'         => false,
            'memory'     => 128,
            'php'        => '8.1',
            'mysql'      => '5.6',
        ];

        $connection = Db::connection(
            Safe::data(
                $_POST,
                [
                    'database' => 'trim',
                    'username' => 'trim',
                    'password' => 'trim',
                    'host'     => 'trim',
                    'prefix'   => 'trim',
                    'driver'   => 'trim:mysql',
                ]
            )->apply()
        );

        $data = array_map(fn ($check, $value) => [
            $check => match ($check) {
                'php'        => version_compare($value, strval(phpversion()), '<='),
                'mysql'      => version_compare($value, $connection->version(), '<='),
                'memory'     => intval(ini_get('memory_limit')) >= intval($value),
                'connection' => $connection instanceof Manager,
                default      => extension_loaded($check),
            },
        ], array_keys($checks), $checks);

        return array_merge(...$data);
    }

    /**
     * Run Expansa installation.
     *
     * @since 2025.1
     */
    public static function install(): array
    {
        $protocol = ( ! empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? 'https://' : 'http://' );
        $siteurl  = $protocol . $_SERVER['SERVER_NAME'];

        // TODO: check sanitize rules & add validator
        [ $site, $userdata, $database ] = Safe::data(
            $_POST,
            [
                'site.name'     => 'trim',
                'site.tagline'  => 'trim',
                'site.url'      => "trim:{$siteurl}|url",
                'user.login'    => 'trim',
                'user.email'    => 'email',
                'user.password' => 'trim',
                'db.database'   => 'trim',
                'db.username'   => 'trim',
                'db.password'   => 'trim',
                'db.host'       => 'trim',
                'db.prefix'     => 'snakecase',
            ]
        )->values();

        /**
         * The check for connection to the database should have already been passed by this point.
         * Therefore, just fill in the file env.php data and immediately connect it.
         *
         * @since 2025.1
         */
        $config = EX_PATH . 'env.php';
        Disk::file(EX_PATH . 'env-sample.php')->copy('envsss')->rewrite(
            array_combine(
                [
                    'db.name',
                    'db.username',
                    'db.password',
                    'db.host',
                    'db.prefix',
                ],
                $database
            )
        );
        if (! file_exists($config)) {
            Disk::file(EX_PATH . 'env-sample.php')->copy('env')->rewrite(
                array_combine(
                    [
                        'db.name',
                        'db.username',
                        'db.password',
                        'db.host',
                        'db.prefix',
                    ],
                    $database
                )
            );
        }

        require_once $config;

        Hook::configure(EX_PATH . 'app/Listeners');

        /**
         * Creating the necessary tables in the database
         *
         * @since 2025.1
         */
        Hook::call('createMainDatabaseTables');

        Db::updateSchema();

        /**
         * Fill same options
         *
         * @since 2025.1
         */
        Option::update('site', $site);

        $user = User::add($userdata);
        if ($user instanceof User) {
            User::login($userdata);

            return [
                'installed' => $user instanceof User,
            ];
        }

        return Error::get();
    }
}
