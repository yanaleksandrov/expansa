<?php

declare(strict_types=1);

namespace Expansa\Database\Contracts;

interface Arrayable
{
    /**
     * Convert object to array
     *
     * @return array
     */
    public function toArray(): array;
}
