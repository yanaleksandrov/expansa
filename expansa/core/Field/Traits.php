<?php

declare(strict_types=1);

namespace Expansa\Field;

trait Traits
{
    /**
     * The ID of the associated object.
     *
     * @var null|int
     */
    private ?int $entityId;

    /**
     * DB entityColumn name.
     *
     * @var null|string
     */
    private ?string $entityColumn;

    /**
     * The name of the database table for the object.
     *
     * @var null|string
     */
    private ?string $table;

    /**
     * Cache key prefix.
     *
     * @var null|string
     */
    private ?string $cacheGroup;
}
