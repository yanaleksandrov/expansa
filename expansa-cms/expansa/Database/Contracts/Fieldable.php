<?php

declare(strict_types=1);

namespace Expansa\Database\Contracts;

/**
 * Anything that can own a dynamic set of meta fields (see {@see \App\Models\Field}) —
 * currently every {@see \Expansa\Database\Model} subclass, plus App\Models\Post until
 * it's converted to a real Model itself.
 */
interface Fieldable
{
    /**
     * Primary key of the owning row, or 0 if it hasn't been persisted yet.
     */
    public function getId(): int;

    /**
     * Name of the owning row's database table.
     */
    public function getTable(): string;
}
