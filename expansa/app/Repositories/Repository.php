<?php

declare(strict_types=1);

namespace app\Repositories;

use App\Models\Model;
use Expansa\Database\Db;

abstract class Repository
{
    protected static string $table;

    protected static string $model;

    protected static bool $softDelete = false;

    public static function all(): array
    {
        $query = DB::table(static::$table)->orderBy('created_at', 'desc');

        if (static::$softDelete) {
            $query->whereNull('deleted_at');
        }

        return static::format($query->get());
    }

    public static function get(int $id): ?object
    {
        $query = DB::table(static::$table)->where('id', $id);

        if (static::$softDelete) {
            $query->whereNull('deleted_at');
        }

        return static::format($query->first());
    }

    public static function store(Model $model): int|false
    {
        if (is_null($model->id)) {
            if (DB::table(static::$table)->insert($model->getAttributes()) > 0) {
                $model->id = (int) DB::lastInsertId();
                return $model->id;
            }
        } else {
            $payload = $model->getChangedAttributes();

            if (DB::table(static::$table)->where('id', $model->id)->update($payload) > 0) {
                return $model->id;
            }
        }

        return false;
    }

    protected static function format(array|object|null $result): mixed
    {
        if (is_array($result)) {
            return array_map(fn ($attributes) => new static::$model((array) $attributes), $result);
        }

        if (is_object($result)) {
            return new static::$model((array) $result);
        }

        return null;
    }
}
