<?php

declare(strict_types=1);

namespace app\User;

use app\Field;
use Expansa\Database\Db;
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
}
