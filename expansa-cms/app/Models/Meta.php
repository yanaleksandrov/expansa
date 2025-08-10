<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Facades\Cache;
use Expansa\Facades\Db;

/**
 * Class Meta
 *
 * Represents a dynamic meta model that can be bound to any database table.
 * Allows mass assignment of all fields and accepts initial attributes.
 *
 * @property string $table     The database table associated with the model.
 * @property array  $fillable  Fields allowed for mass assignment.
 */
class Meta extends Model
{
    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table;

    protected Model $post;

    public function __construct(Model $post, array $attributes = [])
    {
        parent::__construct($attributes);

        $this->post  = $post;
        $this->table = "{$post->getTable()}_fields";
    }

    /**
     * Retrieves the value of a specific field for the associated object.
     *
     * Benchmark when there are 1 million rows: 1 time - 0.00068 sec, 100000 times - 0.31 sec.
     *
     * @param string $key      The key of the field to retrieve. If empty, get all fields of object.
     * @param bool   $isSingle Whether to limit the result to a single value (default: true).
     *
     * @return mixed The field value or null if the object ID is not set.
     */
    public function get(string $key = '', bool $isSingle = true): mixed
    {
        $fields = Cache::get("{$this->post->id}", $this->table, function () {
            $items = Db::select($this->table, ['key', 'value'], ['post_id' => $this->post->id]);

            if (!is_array($items)) {
                return null;
            }

            $result = [];
            foreach ($items as ['key' => $key, 'value' => $value]) {
                $result[$key][] = $value;
            }
            return $result;
        });

        if ($key === '') {
            return $fields;
        }

        return $isSingle ? ($fields[$key][0] ?? null) : ($fields[$key] ?? null);
    }
}
