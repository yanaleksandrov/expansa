<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Contracts;

interface Arrayable
{
    /**
     * Convert object to array
     *
     * @return array
     */
    public function toArray(): array;
}
