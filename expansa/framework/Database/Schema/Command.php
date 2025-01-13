<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Expansa\Database\Abstracts\Base;
use Expansa\Patterns\Fluent;

class Command extends Base
{
    public function compile(Table $table): string
    {
        $sql = '';
        echo '<pre>';
        print_r($table);
        echo '</pre>';
        foreach ($table->getCommands() as $command) {
            $sql .= match ($command->name) {
                'createUlid' => $this->compileOnCreateUlid($command, $table),
                'foreign'    => $this->compileForeignCommand($command),
                'primary'    => $this->compilePrimary($table, $command),
                'index'      => $this->compileIndex($table, $command),
                'unique'     => $this->compileUnique($table, $command),
                default      => '',
            } . PHP_EOL;
        }
        return $sql;
    }

    protected function compilePrimary(Table $table, Fluent $command): string
    {
        return $this->compileIndexBase($table, $command, 'PRIMARY KEY');
    }

    protected function compileIndex(Table $table, Fluent $command): string
    {
        return $this->compileIndexBase($table, $command, 'INDEX');
    }

    protected function compileUnique(Table $table, Fluent $command): string
    {
        return $this->compileIndexBase($table, $command, 'UNIQUE');
    }

    protected function compileIndexBase(Table $table, Fluent $command, string $type): string
    {
        $length = str_starts_with(EX_DB_CHARSET, 'utf8mb4') ?
            sprintf('(%d)', EX_DB_MAX_INDEX_LENGTH) : '';

        return sprintf(
            'ALTER TABLE %s ADD %s %s%s (`%s`%s)',
            $this->wrap($table->name),
            $type,
            $this->wrap($command->index ?? ''),
            isset($command->algorithm) ? ' USIGN ' . $command->algorithm : '',
            $this->columnize($command->columns ?? ''),
            $length
        );
    }

    protected function columnize(string|array $columns): string
    {
        return implode(', ', array_map([$this, 'wrap'], (array) $columns));
    }

    private function compileOnDeleteCascade(CommandForeign $command): string
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

    private function compileForeignCommand($command): string
    {
        return match ($command->onDelete) {
            'CASCADE' => $this->compileOnDeleteCascade($command),
            default   => '',
        };
    }

    private function compileOnCreateUlid(Fluent $command, Table $table): string
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
