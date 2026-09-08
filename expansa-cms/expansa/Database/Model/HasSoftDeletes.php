<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

trait HasSoftDeletes
{
    protected string $deletedAt = 'deleted_at';

    protected bool $softDelete = true;

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return $this->deletedAt;
    }
}
