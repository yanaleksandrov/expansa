<?php

declare(strict_types=1);

namespace Expansa\User;

use Expansa\Db;
use Expansa\Field;
use Expansa\I18n;
use Expansa\Safe;
use Expansa\Validator;

class Schema
{
    /**
     * DB table name.
     *
     * @var string
     */
    public static string $table = 'users';

    /**
     * User data sanitizer.
     *
     * @param array $userdata
     * @return array
     */
    public static function sanitize(array $userdata): array
    {
        return Safe::data(
            $userdata,
            [
                'login'    => 'login',
                'password' => 'trim',
                'email'    => 'email',
                'showname' => 'ucfirst:$login',
                'nicename' => 'slug:$login|unique',
            ]
        )->extend(
            'unique',
            function ($value) {
                $suffix = 1;
                while (Db::select(self::$table, 'id', ['nicename' => $value . ($suffix > 1 ? "-$suffix" : '')])) {
                    $suffix++;
                }
                return sprintf('%s%s', $value, $suffix > 1 ? "-$suffix" : '');
            }
        )->apply();
    }

    /**
     * User data validation
     *
     * @param array $userdata
     * @return array|Validator
     */
    public static function validate(array $userdata): Validator|array
    {
        return Validator::data(
            $userdata,
            [
                'login'    => 'lengthMin:3|lengthMax:60',
                'password' => 'required',
                'email'    => 'email|unique',
            ]
        )->extend(
            'email:unique',
            I18n::_t('Sorry, that email address or login is already used!'),
            fn($validator) => ! self::exists(
                [
                    'login' => $validator->fields['login'],
                    'email' => $validator->fields['email'],
                ]
            )
        )->apply();
    }

    /**
     * Create new table into database.
     */
    public static function migrate(): void
    {
        $tableName      = (new Db\Handler())->getTableName(self::$table);
        $charsetCollate = (new Db\Handler())->getCharsetCollate();

        Db::query(
            "
			CREATE TABLE IF NOT EXISTS {$tableName} (
				id            bigint(20)   UNSIGNED NOT NULL AUTO_INCREMENT,
				login         varchar(60)  NOT NULL DEFAULT '',
				password      varchar(255) NOT NULL DEFAULT '',
				nicename      varchar(60)  NOT NULL DEFAULT '',
				firstname     varchar(60)  NOT NULL DEFAULT '',
				lastname      varchar(60)  NOT NULL DEFAULT '',
				showname      varchar(255) NOT NULL DEFAULT '',
				email         varchar(100) NOT NULL DEFAULT '',
				locale        varchar(100) NOT NULL DEFAULT '',
				registered    DATETIME     NOT NULL DEFAULT NOW(),
				visited       DATETIME     NOT NULL DEFAULT NOW(),
				PRIMARY KEY   (id),
				KEY login_key (login),
				KEY nicename  (nicename),
				KEY email     (email)
			) ENGINE=InnoDB {$charsetCollate};"
        )->fetchAll();

        Field\Schema::migrate($tableName, 'user');
    }
}
