<?php

declare(strict_types=1);

namespace app\Repositories;

use app\Models\Users;
use Expansa\Database\Db;

class UserRepository extends Repository
{
    protected static string $table = 'users';

    protected static string $model = Users::class;

    protected static bool $softDelete = false;

    public static function hasByLogin(string $login): bool
    {
        return DB::table(static::$table)->where('login', $login)->count() > 0;
    }

    public static function getByLogin(string $login): ?Users
    {
        return static::format(
            DB::table(static::$table)
                ->where('login', $login)
                ->get()
        );
    }
}
