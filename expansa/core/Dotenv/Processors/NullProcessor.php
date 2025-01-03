<?php

declare(strict_types=1);

namespace Expansa\Dotenv\Processors;

/**
 * NullProcessor
 *
 * This processor determines if a value is "null" or "NULL" and, if so, returns a null value.
 *
 * @extends AbstractProcessor
 */
class NullProcessor extends AbstractProcessor
{
    /**
     * Checks if the value can be processed.
     *
     * The method determines if the current value is either the string "null" or "NULL".
     *
     * @return bool True if the value can be processed, false otherwise.
     */
    public function canBeProcessed(): bool
    {
        return in_array($this->value, ['null', 'NULL'], true);
    }

    /**
     * Executes the processing logic.
     *
     * This method returns `null` as the processed result.
     *
     * @return mixed Always returns null.
     */
    public function execute(): mixed
    {
        return null;
    }
}
