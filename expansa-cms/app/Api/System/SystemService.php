<?php

declare(strict_types=1);

namespace App\Api\System;

use App\Models\Option;
use App\Models\User;
use App\Support\Installation;
use App\Support\Mailer;
use App\Support\Passwords;
use App\Support\Requirements;
use Expansa\Database\Query\Builder;
use Expansa\Facades\Auth;
use Expansa\Facades\Db;
use Expansa\Facades\Hook;
use Expansa\Facades\Safe;
use Expansa\Facades\Validator;
use Expansa\Http\Exceptions\HttpError;
use Expansa\Http\Exceptions\ValidationFailed;
use Expansa\Support\Arr;

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
        if (!Installation::hasEnvironment()) {
            require_once EX_PATH . 'env.example.php';
        }

        // with env.php the installer does not ask for the database: its settings are checked
        $data = Installation::hasEnvironment() ? EX_DB : Safe::data($input, [
            'database' => 'trim',
            'username' => 'trim',
            'password' => 'trim',
            'host'     => 'trim',
            'prefix'   => 'trim',
            'driver'   => 'trim:' . EX_DB['driver'],
            'charset'  => 'trim:' . EX_DB['charset'],
            'port'     => 'trim:' . EX_DB['port'],
            'error'    => 'trim:' . EX_DB['error'],
        ])->apply();

        try {
            $connection = new Builder($data);
        } catch (\Throwable) {
            $connection = null;
        }

        $connected = $connection instanceof Builder;
        $mysql     = $connected && Requirements::database($connection->version());

        $compat = array_map(
            fn($requirement) => match ($requirement) {
                'php'        => Requirements::php(),
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
     * @throws HttpError       When Expansa is already installed.
     * @throws ValidationFailed When required fields are missing, the env file can't be
     *                              written, or the owner account is invalid.
     */
    public function install(array $input): array
    {
        if (Installation::isComplete()) {
            throw new HttpError(409, t('Expansa is already installed.'));
        }

        // an existing env.php keeps its settings: the configure phase has already connected its database
        $hasEnvironment = Installation::hasEnvironment();
        $this->validateInstallInput($input, $hasEnvironment);

        $protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
        $siteUrl  = $protocol . $_SERVER['SERVER_NAME'];

        [$site, $userdata, $database, $smtp] = Safe::data($input, [
            'site.name'        => 'text',
            'site.tagline'     => 'text',
            'site.url'         => "url:$siteUrl",
            'user.is_verified' => 'bool',
            'user.email'       => 'email',
            'user.locale'      => 'locale',
            'user.login'       => 'trim',
            'user.password'    => 'trim',
            'user.status'      => 'trim:active',
            'db.database'      => 'trim',
            'db.username'      => 'trim',
            'db.password'      => 'trim',
            'db.host'          => 'trim',
            'db.prefix'        => 'snakecase',
            'smtp.host'        => 'trim',
            'smtp.port'        => 'trim:465',
            'smtp.username'    => 'trim',
            'smtp.password'    => 'trim',
            'smtp.from'        => 'trim',
        ])->values();

        // env.php is published only after every step succeeded
        $draft = $hasEnvironment ? null : Installation::draft(
            array_combine(['db.name', 'db.username', 'db.password', 'db.host', 'db.prefix'], $database) + [
                'auth.key'  => bin2hex(random_bytes(32)),
                'nonce.key' => bin2hex(random_bytes(32)),
                'hash.key'  => bin2hex(random_bytes(32)),
            ]
        );

        try {
            if ($draft !== null) {
                // the rest of the installation reads the new constants, e.g. EX_DB
                require_once $draft;

                Db::configure(...EX_DB);
            }

            Hook::run('createMainDatabaseTables');

            Db::updateSchema();

            // The constructor calls fill() internally, which routes through setAttribute() -
            // required so the password attribute's set-mutator actually hashes it before it's
            // saved (unlike make(), which bypasses that entirely for already-trusted data).
            $user = new User($userdata);

            if (!$user->isValid()) {
                throw new ValidationFailed(t('Unable to create the owner account.'), $user->getValidatorErrors());
            }

            // roles are not mass-assignable; the "register" phase that adds them does not run before install
            $user->roles = ['admin'];
            $user->save();

            // the owner marks the installation as complete, see Installation::isComplete()
            Option::update('site', $site + ['owner' => ['email' => $user->email]]);

            // the Mail tab of the settings edits it later
            Option::update('mail', Mailer::normalize($smtp + ['encryption' => (int) $smtp['port'] === 587 ? 'tls' : 'ssl']));

            if ($draft !== null) {
                Installation::complete($draft);
            }
        } catch (\Throwable $e) {
            if ($draft !== null) {
                Installation::discard($draft);
            }

            // a retry would otherwise fail on the owner's login and email being taken
            if (isset($user->id)) {
                try {
                    Db::delete($user->table, ['id' => $user->id]);
                } catch (\Throwable) {
                }
            }

            throw $e;
        }

        // the owner signs in right away, without the new device warning: it is the installation itself
        Auth::login($user, remember: true);

        return [
            'target'        => 'body',
            'redirect:7000' => url('installed'),
        ];
    }

    /**
     * Rejects the request before any side effect (writing env.php, creating tables, ...)
     * runs, instead of letting missing fields surface deep inside model validation.
     *
     * @throws ValidationFailed
     */
    private function validateInstallInput(array $input, bool $hasEnvironment): void
    {
        $rules = [
            'site.name'     => 'required',
            'user.email'    => 'required|email',
            'user.login'    => 'required',
            'user.password' => 'required',
            'user.locale'   => 'required',
        ];

        // the database settings come from env.php when it exists
        if (! $hasEnvironment) {
            $rules += [
                'db.database' => 'required',
                'db.username' => 'required',
                'db.password' => 'required',
                'db.host'     => 'required',
                'db.prefix'   => 'required',
            ];
        }

        $validator = Validator::data(Arr::dot($input), $rules)->apply();

        if (!$validator->isValid()) {
            throw new ValidationFailed(t('Please fill in all required fields.'), $validator->errors);
        }

        // the owner account opens the whole site: the same password rules as everywhere
        $owner    = (array) ($input['user'] ?? []);
        $personal = [(string) ($owner['login'] ?? ''), (string) ($owner['email'] ?? '')];
        $refusal  = Passwords::check(trim((string) ($owner['password'] ?? '')), $personal);
        if ($refusal !== null) {
            throw new ValidationFailed($refusal, ['user.password' => [$refusal]]);
        }
    }
}
