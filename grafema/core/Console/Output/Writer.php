<?php

declare(strict_types=1);

namespace Grafema\Console\Output;

use Stringable;

/**
 * Class Command.
 *
 * @package cli
 */
trait Writer
{
	protected string $reset = "\033[0m";

	/**
	 * Parse and show in console.
	 *
	 * @param string $text The input text.
	 * @return void
	 */
	protected function info(string $text): void
	{
		fwrite(STDOUT, $this->decorate($text) . PHP_EOL);
	}

	/**
	 * Parses the input text and replaces AsciiDoc color and style syntax
	 * with corresponding CLI colors and styles.
	 *
	 * @param string $text The input text.
	 * @return string
	 */
	protected function decorate(string $text): string
	{
		// replacing all occurrences of [color,style]#text# with the equivalent color and style in the CLI
		return preg_replace_callback('/\[(\w+(?:,\s*\w+)*)\]#([^#]+)#/', function ($matches) {
			$attributes = explode(',', $matches[1]);
			$text = $matches[2];

			$colorCode = '';
			$styleCode = '';
			foreach ($attributes as $attribute) {
				$attribute = strtoupper(trim($attribute));

				// check text color
				$color = Color::fromName(Color::cases(), $attribute);
				if ($color !== null) {
					$colorCode = $color->value;
				}

				// check text style
				$style = Style::fromName(Style::cases(), $attribute);
				if ($style !== null) {
					$styleCode .= $style->value;
				}
			}

			return $colorCode . $styleCode . $text . $this->reset;
		}, $text);
	}

	protected function decorateOptions(string $options): string
	{
		return implode(', ', array_map(function($item) {
			return $this->decorate(sprintf('[yellow]#%s#', trim($item)));
		}, explode(',', $options)));
	}

	/**
	 * Prints a new line in the output.
	 *
	 * @param int $lines Number of lines to be printed
	 */
	public function newLine(int $lines = 1) : void
	{
		for ($i = 0; $i < $lines; $i++) {
			fwrite(STDOUT, PHP_EOL);
		}
	}

	/**
	 * Performs audible beep alarms.
	 *
	 * @param int $times  How many times should the beep be played
	 * @param int $usleep Interval in microseconds
	 */
	public function beep(int $times = 1, int $usleep = 0) : void
	{
		for ($i = 0; $i < $times; $i++) {
			fwrite(STDOUT, "\x07");
			usleep($usleep);
		}
	}

	/**
	 * Writes a message to STDERR and optionally exit with a custom code.
	 *
	 * @param string   $message  The error message
	 * @param int|null $exitCode Set null to do not exit
	 */
	public function error(string $message, ?int $exitCode = 1) : void
	{
		$this->beep();
		fwrite(STDERR, $this->decorate("[red]#$message#") . PHP_EOL);
		if ($exitCode !== null) {
			exit($exitCode);
		}
	}

	/**
	 * Clear the terminal screen.
	 */
	public function clear() : void
	{
		fwrite(STDOUT, "\e[H\e[2J");
	}

	/**
	 * Creates a "live line".
	 *
	 * Erase the current line, move the cursor to the beginning of the line and writes a text.
	 *
	 * @param string $text     The text to be written
	 * @param bool   $finalize If true the "live line" activity ends, creating a new line after the text
	 */
	public function liveLine(string $text, bool $finalize = false) : void
	{
		// See: https://stackoverflow.com/a/35190285
		$string = '';
		if (PHP_OS_FAMILY !== 'Windows') {
			$string .= "\33[2K";
		}

		$string .= "\r" . $text;
		if ($finalize) {
			$string .= PHP_EOL;
		}
		fwrite(STDOUT, $string);
	}

	/**
	 * Creates a well formatted table.
	 *
	 * @param array<array<Stringable|scalar>> $tbody Table body rows
	 * @param array<Stringable|scalar> $thead Table head fields
	 */
	public function table(array $tbody, array $thead = []) : void
	{
		// All the rows in the table will be here until the end
		$tableRows = [];
		// We need only indexes and not keys
		if (!empty($thead)) {
			$tableRows[] = array_values($thead);
		}
		foreach ($tbody as $tr) {
			// cast tr to array if is not - (objects...)
			$tableRows[] = array_values((array) $tr);
		}
		// Yes, it really is necessary to know this count
		$totalRows = count($tableRows);
		// Store all columns lengths
		// $allColsLengths[row][column] = length
		$allColsLengths = [];
		// Store maximum lengths by column
		// $maxColsLengths[column] = length
		$maxColsLengths = [];
		// Read row by row and define the longest columns
		for ($row = 0; $row < $totalRows; $row++) {
			$column = 0; // Current column index
			foreach ($tableRows[$row] as $col) {
				// Sets the size of this column in the current row
				$allColsLengths[$row][$column] = $this->strlen((string) $col);
				// If the current column does not have a value among the larger ones
				// or the value of this is greater than the existing one
				// then, now, this assumes the maximum length
				if (!isset($maxColsLengths[$column])
					|| $allColsLengths[$row][$column] > $maxColsLengths[$column]) {
					$maxColsLengths[$column] = $allColsLengths[$row][$column];
				}
				// We can go check the size of the next column...
				$column++;
			}
		}
		// Read row by row and add spaces at the end of the columns
		// to match the exact column length
		for ($row = 0; $row < $totalRows; $row++) {
			$column = 0;
			foreach ($tableRows[$row] as $col => $value) {
				$diff = $maxColsLengths[$column] - $allColsLengths[$row][$col];
				if ($diff) {
					$tableRows[$row][$column] .= str_repeat(' ', $diff);
				}
				$column++;
			}
		}
		$table = $line = '';
		// Joins columns and append the well formatted rows to the table
		foreach ($tableRows as $row => $value) {
			// Set the table border-top
			if ($row === 0) {
				$line = '+';
				foreach (array_keys($value) as $col) {
					$line .= str_repeat('-', $maxColsLengths[$col] + 2) . '+';
				}
				$table .= $line . PHP_EOL;
			}
			// Set the vertical borders
			$table .= '| ' . implode(' | ', $value) . ' |' . PHP_EOL;
			// Set the thead and table borders-bottom
			if (($row === 0 && !empty($thead)) || $row + 1 === $totalRows) {
				$table .= $line . PHP_EOL;
			}
		}
		fwrite(STDOUT, $table);
	}
}
