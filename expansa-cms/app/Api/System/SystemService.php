<?php

declare(strict_types=1);

namespace App\Api\System;

use App\Models\Options;
use App\Models\User;
use Expansa\Database\Query\Builder;
use Expansa\Facades\Db;
use Expansa\Facades\Disk;
use Expansa\Facades\Hook;
use Expansa\Facades\Safe;
use Expansa\Facades\Validator;
use Expansa\Http\Exceptions\HttpException;
use Expansa\Http\Exceptions\ValidationException;
use Expansa\Support\Arr;
use Expansa\Support\Is;

/**
 * Business logic for the installer, moved out of the controller. Talks to
 * models/facades directly; the controller never does.
 */
final class SystemService
{
    private const REQUIREMENTS = ['connection', 'pdo', 'curl', 'mbstring', 'gd', 'memory', 'php', 'mysql'];

    /**
     * Checks the server + a candidate database connection against the minimum requirements.
     */
    public function checkRequirements(array $input): array
    {
        if (!is_file(EX_PATH . 'env.php')) {
            require_once EX_PATH . 'env.example.php';
        }

        $data = Safe::data($input, [
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
            $connection = new Builder($data);
        } catch (\Throwable) {
            $connection = null;
        }

        $connected = $connection instanceof Builder;
        $mysql     = $connected && version_compare($connection->version(), EX_REQUIRED_MYSQL_VERSION, '>=');

        $compat = array_map(
            fn($requirement) => match ($requirement) {
                'php'        => version_compare(phpversion(), EX_REQUIRED_PHP_VERSION, '>='),
                'memory'     => intval(ini_get('memory_limit')) >= EX_REQUIRED_MEMORY,
                'mysql'      => $mysql,
                'connection' => $connected,
                default      => extension_loaded($requirement),
            },
            array_combine(self::REQUIREMENTS, self::REQUIREMENTS)
        );

        return [
            'isCompatible' => !in_array(false, $compat, true),
            'compat'       => $compat,
        ];
    }

    /**
     * Writes the environment config, creates the schema, and creates the owner account.
     *
     * @throws HttpException       When Expansa is already installed.
     * @throws ValidationException When required fields are missing, the env file can't be
     *                              written, or the owner account is invalid.
     */
    public function install(array $input): array
    {
        if (Is::installed()) {
            throw new HttpException(409, t('Expansa is already installed.'));
        }

        $this->validateInstallInput($input);

        $protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
        $siteUrl  = $protocol . $_SERVER['SERVER_NAME'];

        [$site, $userdata, $database] = Safe::data($input, [
            'site.name'        => 'text',
            'site.tagline'     => 'text',
            'site.url'         => "url:$siteUrl",
            'user.is_verified' => 'bool',
            'user.email'       => 'email',
            'user.locale'      => 'locale',
            'user.login'       => 'trim',
            'user.password'    => 'trim',
            'db.database'      => 'trim',
            'db.username'      => 'trim',
            'db.password'      => 'trim',
            'db.host'          => 'trim',
            'db.prefix'        => 'snakecase',
        ])->values();

        // The connection check should have already passed by this point (see checkRequirements());
        // here we just persist it and connect to it.
        $config = EX_PATH . 'env.php';
        if (!is_file($config)) {
            $env = Disk::file(EX_PATH . 'env.example.php')->copy('env');
            if ($env->errors) {
                throw new ValidationException(t('Unable to write the environment configuration file.'), $env->errors);
            }

            Disk::file(EX_PATH . 'env.php')->rewrite(
                array_combine(['db.name', 'db.username', 'db.password', 'db.host', 'db.prefix'], $database)
            );
        }

        require_once $config;

        Hook::configure(EX_PATH . 'app/Listeners');
        Hook::call('createMainDatabaseTables');

        Db::updateSchema();

        $user = User::create($userdata);

        if (!$user->isValid()) {
            throw new ValidationException(t('Unable to create the owner account.'), $user->getValidatorErrors());
        }

        $user->save();

        Options::update('site', $site + ['owner' => ['email' => $user->email]]);

        User::login($userdata);

        return [
            ['target' => 'body', 'redirect' => url('installed')],
        ];
    }

    /**
     * Rejects the request before any side effect (writing env.php, creating tables, ...)
     * runs, instead of letting missing fields surface deep inside model validation.
     *
     * @throws ValidationException
     */
    private function validateInstallInput(array $input): void
    {
        $validator = Validator::data(Arr::dot($input), [
            'site.name'     => 'required',
            'user.email'    => 'required|email',
            'user.login'    => 'required',
            'user.password' => 'required',
            'user.locale'   => 'required',
            'db.database'   => 'required',
            'db.username'   => 'required',
            'db.password'   => 'required',
            'db.host'       => 'required',
            'db.prefix'     => 'required',
        ])->apply();

        if (!$validator->isValid()) {
            throw new ValidationException(t('Please fill in all required fields.'), $validator->getErrors());
        }
    }
}
