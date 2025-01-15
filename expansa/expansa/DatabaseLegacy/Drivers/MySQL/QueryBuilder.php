<?php

declare(strict_types=1);

namespace Expansa\DatabaseLegacy\Drivers\MySQL;

use Expansa\DatabaseLegacy\Query\Builder;

class QueryBuilder extends Builder
{
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike', 'not ilike',
        '~', '&', '|', '#', '<<', '>>', '<<=', '>>=',
        '&&', '@>', '<@', '?', '?|', '?&', '||', '-', '@?', '@@', '#-',
        'is distinct from', 'is not distinct from',
    ];
}
