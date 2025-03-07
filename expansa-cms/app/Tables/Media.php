<?php

declare(strict_types=1);

namespace App\Tables;

use Expansa\Builders\Table;

final class Media extends Table
{
    public function data(): array
    {
        return [];
    }

    public function columns(): array
    {
        return [
            $this->cell('media')->view('media'),
        ];
    }
}
