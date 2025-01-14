<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\Table;
use Expansa\Patterns\Fluent;

trait Triggers
{
    protected function compileTriggers(Table $table, Fluent $command): string
    {
        return match ($command->name) {
            'createUlid' => $this->compileOnCreateUlid($table),
            'foreign'    => $this->compileOnDeleteCascade($command),
            default      => '',
        };
    }

    protected function compileOnDeleteCascade(Fluent $command): string
    {
        if (!isset($command->on, $command->column, $command->references)) {
            return '';
        }

        return "
CREATE TRIGGER cascade_delete_{$command->on}
    AFTER DELETE ON <$command->on>
    FOR EACH ROW
        BEGIN
            DELETE FROM <{$command->on}_fields> WHERE $command->column = OLD.$command->references;
        END;";
    }

    protected function compileOnCreateUlid(Table $table): string
    {
        return "
CREATE TRIGGER before_insert_$table->name
    BEFORE INSERT ON <$table->name>
    FOR EACH ROW
        BEGIN
            IF NEW.ulid IS NULL THEN
                SET NEW.ulid = UPPER(
                    CONCAT(UNHEX(CONV(ROUND(UNIX_TIMESTAMP(CURTIME(4))*1000), 10, 16)), RANDOM_BYTES(10))
                );
            END IF;
        END;";
    }
}
