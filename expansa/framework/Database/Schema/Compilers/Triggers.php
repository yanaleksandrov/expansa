<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\CommandForeign;
use Expansa\Database\Schema\Table;
use Expansa\Patterns\Fluent;

trait Triggers
{
    protected function compileOnDeleteCascade(CommandForeign $command): string
    {
        if (!isset($command->on, $command->column, $command->references)) {
            return '';
        }

        $prefix = EX_DB_PREFIX;

        return "
CREATE TRIGGER cascade_delete_$prefix{$command->on}
    AFTER DELETE ON $prefix{$command->on}
    FOR EACH ROW
        BEGIN
            DELETE FROM $prefix{$command->on}_fields WHERE $command->column = OLD.$command->references;
        END;";
    }

    protected function compileForeignCommand($command): string
    {
        return match ($command->onDelete) {
            'CASCADE' => $this->compileOnDeleteCascade($command),
            default   => '',
        };
    }

    protected function compileOnCreateUlid(Fluent $command, Table $table): string
    {
        if (!isset($command->name)) {
            return '';
        }
        $prefix = EX_DB_PREFIX;

        return "
CREATE TRIGGER before_insert_$prefix{$table->name}
    BEFORE INSERT ON $prefix{$table->name}
    FOR EACH ROW
        BEGIN
            IF NEW.$command->name IS NULL THEN
                SET NEW.$command->name = UPPER(
                    CONCAT(
                        LPAD(HEX(UNIX_TIMESTAMP(CURTIME(4)) * 1000), 12, '0'),
                        HEX(RANDOM_BYTES(10))
                    )
                );
            END IF;
        END;";
    }
}
