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
            'createUuid' => $this->compileOnCreateUuid($table),
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
                    UPPER(CONCAT(LPAD(HEX(UNIX_TIMESTAMP(NOW(4)) * 1000), 12, '0'), HEX(RANDOM_BYTES(10))))
                );
            END IF;
        END;";
    }

    protected function compileOnCreateUuid(Table $table): string
    {
        return "
CREATE TRIGGER before_insert_$table->name
    BEFORE INSERT ON <$table->name>
    FOR EACH ROW
        BEGIN
            IF NEW.uuid IS NULL THEN
                SET NEW.uuid = LOWER(CONCAT_WS('-',
                    LPAD(HEX(FLOOR(UNIX_TIMESTAMP(NOW(4)) * 1000)), 12, '0'),
                    CONCAT('7', SUBSTRING(HEX(RANDOM_BYTES(2)), 2)), -- version 7
                    SUBSTRING(HEX(RANDOM_BYTES(2)), 1, 4),
                    SUBSTRING(HEX(RANDOM_BYTES(2)), 1, 4),
                    SUBSTRING(HEX(RANDOM_BYTES(6)), 1, 12)
                ));
            END IF;
        END;";
    }
}
