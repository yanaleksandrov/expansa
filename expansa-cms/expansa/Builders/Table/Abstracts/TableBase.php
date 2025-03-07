<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Abstracts;

abstract class TableBase
{
    abstract public function data(): array;

    abstract public function columns(): array;
}
