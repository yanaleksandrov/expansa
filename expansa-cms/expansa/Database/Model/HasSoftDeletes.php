<?php

declare(strict_types=1);

namespace Expansa\Database\Model;

trait HasSoftDeletes
{
    /**
     * Column soft-deleted rows are stamped in - see {@see self::getDeletedAtColumn()}.
     *
     * @var string
     */
    protected string $deletedAt = 'deleted_at';

    /**
     * Whether this model soft-deletes at all. Not currently read anywhere - {@see \Expansa\Database\Model::usesTrait()}
     * is what Query checks - kept as a per-model override point for a future opt-out.
     *
     * @var bool
     */
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
