<?php

namespace Expansa\Env;

/**
 * BooleanProcessor
 *
 * Processes a string value to determine if it represents a boolean
 * ("true" or "false") and returns its corresponding boolean equivalent.
 */
class BooleanProcessor extends AbstractProcessor
{
    /**
     * Determines if the value can be processed as a boolean.
     *
     * This method checks if the lowercase version of the value is either "true" or "false".
     *
     * @return bool True if the value can be processed as a boolean, false otherwise.
     */
    public function canBeProcessed(): bool
    {
        return in_array(strtolower($this->value), ['true', 'false'], true);
    }

    /**
     * Executes the PHP function and returns a boolean value indicating whether the value is 'true' in lowercase.
     *
     * @return bool The result of the comparison between the lowercase value of $this->value and 'true'.
     */
    public function execute(): bool
    {
        return strtolower($this->value) === 'true';
    }
}
