<?php

namespace Expansa;

use Expansa\Env\AbstractProcessor;
use Expansa\Env\BooleanProcessor;
use Expansa\Env\NullProcessor;
use Expansa\Env\NumberProcessor;
use Expansa\Env\QuotedProcessor;

final class Env
{
    /**
     *
     *
     * @var string $path       The directory where the .env file can be located.
     * @var ?array $processors Configure the options on which the parsed will act.
     */
    private function __construct(
        protected string $path,
        protected ?array $processors
    ) {
        if (!is_file($this->path) || !is_readable($this->path)) {
            throw new \InvalidArgumentException("File '$this->path' does not exist or is not readable");
        }
        $this->setProcessors($processors);
    }

    /**
     * Loads the configuration data from the specified file path.
     * Parses the values into $_SERVER and $_ENV arrays, skipping empty and commented lines.
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

    private function setProcessors(?array $processors = null): void
    {
        if ($processors === null) {
            $this->processors = [
                NullProcessor::class,
                BooleanProcessor::class,
                NumberProcessor::class,
                QuotedProcessor::class
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
     * @return mixed
     */
    private function processValue(string $value): mixed
    {
        // first trim spaces and quotes if configured
        $trimmedValue = trim($value);

        foreach ($this->processors as $processor) {
            /** @var AbstractProcessor $processorInstance */
            $processorInstance = new $processor($trimmedValue);

            if ($processorInstance->canBeProcessed()) {
                return $processorInstance->execute();
            }
        }

        // does not match any processor options, return as is
        return $trimmedValue;
    }
}
