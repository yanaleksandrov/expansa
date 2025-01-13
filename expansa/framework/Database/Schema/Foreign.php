<?php

declare(strict_types=1);

namespace Expansa\Database\Schema;

use Expansa\Patterns\Fluent;

/**
 * This class represents the definition of foreign key constraints in a database schema.
 * It provides methods for setting the referenced table, columns, and actions to be performed
 * on update or delete operations.
 *
 * @method Foreign on(string $table) Specify the referenced table
 * @method Foreign references(string|array $columns) Specify the referenced column(s)
 * @method Foreign onUpdate(string $action)
 * @method Foreign onDelete(string $action)
 */
class Foreign extends Fluent
{
    /**
     * Set ON UPDATE action to RESTRICT;
     *
     * @return $this
     */
    public function onUpdateRestrict(): static
    {
        return $this->onUpdate('RESTRICT');
    }

    /**
     * Set ON UPDATE action to SET NULL;
     *
     * @return $this
     */
    public function onUpdateSetNull(): static
    {
        return $this->onUpdate('SET NULL');
    }

    /**
     * Set ON UPDATE action to SET DEFAULT;
     *
     * @return $this
     */
    public function onUpdateSetDefault(): static
    {
        return $this->onUpdate('SET DEFAULT');
    }

    /**
     * Set ON UPDATE action to CASCADE;
     *
     * @return $this
     */
    public function onUpdateCascade(): static
    {
        return $this->onUpdate('CASCADE');
    }

    /**
     * Set ON DELETE action to RESTRICT;
     *
     * @return $this
     */
    public function onDeleteRestrict(): static
    {
        return $this->onDelete('RESTRICT');
    }

    /**
     * Set ON DELETE action to SET NULL;
     *
     * @return $this
     */
    public function onDeleteSetNull(): static
    {
        return $this->onDelete('SET NULL');
    }

    /**
     * Set ON DELETE action to SET DEFAULT;
     *
     * @return $this
     */
    public function onDeleteSetDefault(): static
    {
        return $this->onDelete('SET DEFAULT');
    }

    /**
     * Set ON DELETE action to CASCADE;
     *
     * @return $this
     */
    public function onDeleteCascade(): static
    {
        return $this->onDelete('CASCADE');
    }
}
