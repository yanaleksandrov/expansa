<?php

declare(strict_types=1);

namespace Expansa\Console\Input;

use Expansa\Console\Output\Writer;

class Reader
{
	use Writer;

	/**
	 * Get user input.
	 *
	 * NOTE: It is possible pass multiple lines ending each line with a backslash.
	 *
	 * @param string $prepend Text prepended in the input. Used internally to
	 * allow multiple lines
	 *
	 * @return string Returns the user input
	 */
	public function getInput(string $prepend = '') : string
	{
		$input = fgets(STDIN);
		$input = $input === false ? '' : trim($input);
		$prepend .= $input;
		$eolPos = false;
		if ($prepend) {
			$eolPos = strrpos($prepend, '\\', -1);
		}
		if ($eolPos !== false) {
			$prepend = substr_replace($prepend, PHP_EOL, $eolPos);
			$prepend = $this->getInput($prepend);
		}
		return $prepend;
	}

	/**
	 * Prompt a question.
	 *
	 * @param string $question The question to prompt
	 * @param array<int,string>|string|null $options Answer options. If an array
	 * is set, the default answer is the first value. If is a string, it will
	 * be the default.
	 *
	 * @return string The answer
	 */
	public function prompt(string $question, array | string | null $options = null) : string
	{
		if ($options !== null) {
			$options = is_array($options) ? array_values($options) : [$options];
		}

		if ($options) {
			$opt = $options;
			$opt[0] = $this->decorate("[bold]#$opt[0]#");
			$optionsText = isset($opt[1])
				? implode(', ', $opt)
				: $opt[0];
			$question .= ' [' . $optionsText . ']';
		}
		$question .= ': ';

		fwrite(STDOUT, $question);

		$answer = $this->getInput();
		if ($answer === '' && isset($options[0])) {
			$answer = $options[0];
		}
		return $answer;
	}

	/**
	 * Prompt a question with secret answer.
	 *
	 * @param string $question The question to prompt
	 *
	 * @see https://dev.to/mykeels/reading-passwords-from-stdin-in-php-1np9
	 *
	 * @return string The secret answer
	 */
	public function secret(string $question) : string
	{
		$question .= ': ';
		fwrite(STDOUT, $question);
		exec('stty -echo');
		$secret = trim((string) fgets(STDIN));
		exec('stty echo');
		return $secret;
	}
}
