<?php

declare(strict_types=1);

namespace Expansa\Dotenv\Processors;

class QuotedProcessor extends AbstractProcessor
{
    public function canBeProcessed(): bool
    {
        $wrappedByDoubleQuotes = $this->isWrappedByChar($this->value, '"');
        if ($wrappedByDoubleQuotes) {
            return true;
        }
        return $this->isWrappedByChar($this->value, '\'');
    }

    /**
     * Executes the function and returns a substring of the value property of the current object,
     * excluding the first and last characters.
     *
     * @return string The substring of the value property.
     */
    public function execute(): string
    {
        // since this function is used for the quote removal we don't need mb_substr
        return substr($this->value, 1, -1);
    }

    /**
     * Checks if a string is wrapped by the specified character.
     *
     * This method verifies that the given string starts and ends with the same specified character.
     *
     * @param string $value The string to check.
     * @param string $char The character to check for wrapping.
     *
     * @return bool True if the string is wrapped by the character, false otherwise.
     */
    private function isWrappedByChar(string $value, string $char): bool
    {
        return !empty($value) && $value[0] === $char && $value[-1] === $char;
    }
}
