<?php

declare(strict_types=1);

namespace Expansa\Dotenv;

use Expansa\Dotenv\Exception\InvalidArgumentException;
use Expansa\Dotenv\Processors\NullProcessor;
use Expansa\Dotenv\Processors\QuotedProcessor;
use Expansa\Dotenv\Processors\NumberProcessor;
use Expansa\Dotenv\Processors\BooleanProcessor;
use Expansa\Dotenv\Processors\AbstractProcessor;

/**
 * Class Env
 *
 * Handles loading and processing of environment variables from a `.env` file.
 * This class parses the `.env` file and populates the $_ENV and $_SERVER superglobals.
 * It also supports custom processors to handle specific value transformations.
 *
 * @package Expansa\Dotenv
 */
class Env
{
    /**
     * Env constructor.
     *
     * Initializes the Env instance with a file path and optional processors.
     * Verifies the existence and readability of the `.env` file and configures the processors.
     *
     * @param string $path       The absolute path to the `.env` file.
     * @param ?array $processors Optional array of processor class names to transform values.
     *
     * @throws InvalidArgumentException If the `.env` file does not exist or is not readable.
     */
    private function __construct(
        protected string $path,
        protected ?array $processors
    )
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            throw new InvalidArgumentException("File '$this->path' does not exist or is not readable");
        }
        $this->setProcessors($processors);
    }

    /**
     * Loads the configuration data from the specified file path.
     * Parses the values into $_SERVER and $_ENV arrays, skipping empty and commented lines.
     *
     * @param string     $path       The path to the .env file.
     * @param null|array $processors Optional custom processors for value transformation.
     *
     * @throws InvalidArgumentException
     */
    public static function load(string $path, ?array $processors = null): void
    {
        $env = new self($path, $processors);

        $lines = file($env->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [ $name, $value ] = array_map('trim', explode('=', $line, 2));

            $value = $env->processValue($value);
            if (!isset($_SERVER[$name], $_ENV[$name])) {
                putenv("$name=$value");

                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Configures the processors to be used for value transformation.
     *
     * @param null|array $processors Optional list of processor class names.
     */
    private function setProcessors(?array $processors = null): void
    {
        if ($processors === null) {
            $this->processors = [
                NullProcessor::class,
                BooleanProcessor::class,
                NumberProcessor::class,
                QuotedProcessor::class,
            ];
            return;
        }

        foreach ($processors as $processor) {
            if (is_subclass_of($processor, AbstractProcessor::class)) {
                $this->processors[] = $processor;
            }
        }
    }

    /**
     * Process the value with the configured processors
     *
     * @param string $value The value to process
     * @return string
     */
    private function processValue(string $value): string
    {
        $trimmedValue = trim($value);

        foreach ($this->processors as $processor) {
            $processorInstance = new $processor($trimmedValue);

            if ($processorInstance->canBeProcessed()) {
                return $processorInstance->execute();
            }
        }

        // does not match any processor options, return as is
        return $trimmedValue;
    }
}
