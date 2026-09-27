<?php

declare(strict_types=1);

namespace Expansa\Console;

use Expansa\Console\Traits\WritesOutput;

/**
 * Reads the user input from STDIN: a line, an answer to a question or a hidden secret.
 *
 * @package Expansa\Console
 */
final class Reader
{
    use WritesOutput;

    /**
     * Get a line of input; a line ending with a backslash continues on the next one.
     *
     * @param string $prepend Text read so far.
     * @return string
     */
    public function getInput(string $prepend = ''): string
    {
        $input    = fgets(STDIN);
        $prepend .= $input === false ? '' : trim($input);

        $eolPos = $prepend !== '' ? strrpos($prepend, '\\', -1) : false;
        if ($eolPos !== false) {
            $prepend = $this->getInput(substr_replace($prepend, PHP_EOL, $eolPos));
        }

        return $prepend;
    }

    /**
     * Ask a question, an empty answer gives the default.
     *
     * @param string                        $question
     * @param array<int, string>|string|null $options Answers, the first one is the default; a string is the default.
     * @return string
     */
    public function prompt(string $question, array|string|null $options = null): string
    {
        $options = $options === null ? [] : array_values((array) $options);

        if ($options !== []) {
            $shown    = $options;
            $shown[0] = $this->decorate("[bold]#$shown[0]#");
            $question .= ' [' . implode(', ', $shown) . ']';
        }

        fwrite(STDOUT, $question . ': ');

        $answer = $this->getInput();

        return $answer === '' ? ($options[0] ?? $answer) : $answer;
    }

    /**
     * Ask a question without echoing the answer.
     *
     * @param string $question
     * @return string
     */
    public function secret(string $question): string
    {
        fwrite(STDOUT, $question . ': ');
        exec('stty -echo');
        $secret = trim((string) fgets(STDIN));
        exec('stty echo');

        return $secret;
    }
}
