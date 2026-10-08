<?php

declare(strict_types=1);

namespace Expansa\Log\Contracts;

use Expansa\Log\Record;

interface Formatter
{
    /**
     * Turn a record into the text a handler writes or sends.
     *
     * @param Record $record
     * @return string
     */
    public function format(Record $record): string;
}
