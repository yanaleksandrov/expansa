<?php

declare(strict_types=1);

namespace Expansa\Database\Schema\Compilers;

use Expansa\Database\Schema\Table;
use Expansa\Patterns\Fluent;

trait Triggers
{
    /**
     * Compiles a CREATE TRIGGER statement for a ulid()/uuid() column command, or "" for anything
     * else - a 'foreign' command compiles to a real FOREIGN KEY constraint instead, see
     * {@see Indexes::compileForeignKey()}.
     *
     * @param Table  $table
     * @param Fluent $command One entry from $table->commands.
     * @return string
     */
    protected function compileTriggers(Table $table, Fluent $command): string
    {
        return match ($command->name) {
            'createUlid' => $this->compileOnCreateUlid($table),
            'createUuid' => $this->compileOnCreateUuid($table),
            default      => '',
        };
    }

    /**
     * @param Table $table
     * @return string
     */
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

    /**
     * @param Table $table
     * @return string
     */
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
