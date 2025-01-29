<?php

declare(strict_types=1);

namespace app\Api;

use app\Option;
use app\User;
use Expansa\Db;
use Expansa\Disk;
use Expansa\Error;
use Expansa\Hook;
use Expansa\Json;
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
        $datas = [];
        $requirements = ['connection', 'pdo', 'curl', 'mbstring', 'gd', 'memory', 'php', 'mysql'];

        // Basic constants for the environment
        require_once EX_PATH . 'env.example.php';

        $data = Safe::data($_POST, [
            'database' => 'trim',
            'username' => 'trim',
            'password' => 'trim',
            'host'     => 'trim',
            'prefix'   => 'trim',
            'driver'   => 'trim:' . EX_DB_DRIVER,
            'charset'  => 'trim:' . EX_DB_CHARSET,
            'port'     => 'trim:' . EX_DB_PORT,
            'error'    => 'trim:' . EX_DB_ERROR_MODE,
        ])->apply();

        try {
            $connection = new \Expansa\Database\Query\Builder($data);
        } finally {
            $mysql     = false;
            $connected = false;
            if (! empty($connection)) {
                $mysql     = version_compare($connection->version(), EX_REQUIRED_MYSQL_VERSION, '>=');
                $connected = $connection instanceof \Expansa\Database\Query\Builder;
            }
            header('Content-Type: application/json; charset=utf-8');

            echo Json::encode(
                [
                    'status'    => 200,
                    'benchmark' => metrics()->time(),
                    'memory'    => metrics()->memory(),
                    'data'      => array_map(
                        fn($requirement) => match ($requirement) {
                            'php'        => version_compare(phpversion(), EX_REQUIRED_PHP_VERSION, '>='),
                            'memory'     => intval(ini_get('memory_limit')) >= EX_REQUIRED_MEMORY,
                            'mysql'      => $mysql,
                            'connection' => $connected,
                            default      => extension_loaded($requirement),
                        },
                        array_combine($requirements, $requirements)
                    ),
                    'errors'    => [],
                ],
                true,
                true
            );

            exit();
        }
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
                'site.url'      => "trim:$siteurl|url",
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
        if (! file_exists($config)) {
            Disk::file(EX_PATH . 'env.example.php')->copy('env')->get(EX_PATH . 'env.php')->rewrite(
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
