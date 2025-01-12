<?php

declare(strict_types=1);

namespace app\Models;

use app\Repositories\UserRepository;

/**
 * @property int id
 * @property string login
 * @property string password
 * @property string createdAt
 * @property string updatedAt
 * @property string deletedAt
 */
class Users extends Model
{
    protected static string $repository = UserRepository::class;

    public static function create(string $login, string $password): static
    {
        $user = new static([
            'login'    => $login,
            'password' => hash('sha256', $password),
        ]);

        $user->save();

        return static::get($user->id);
    }
}
