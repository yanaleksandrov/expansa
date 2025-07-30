<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;

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

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<int, string>
     */
    protected array $fillable = ['*'];

    /**
     * Create a new Meta instance.
     *
     * @param string               $table      The database table name.
     * @param array<string,mixed>  $attributes Initial model attributes.
     */
    public function __construct(string $table, array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = $table;
    }
}
