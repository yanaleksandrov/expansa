<?php

declare(strict_types=1);

namespace Expansa\Database;

/**
 * The Medoo raw object.
 */
class Raw
{
    /**
     * The array of mapping data for the raw string.
     *
     * @var array
     */
    public array $map;

    /**
     * The raw string.
     *
     * @var string
     */
    public string $value;
}
