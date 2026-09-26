<?php

declare(strict_types=1);

namespace Expansa\Log\Formatters;

use DateTimeInterface;
use JsonSerializable;
use Stringable;
use Throwable;
use UnitEnum;
use Expansa\Log\Contracts\Formatter;

/**
 * Base formatter: turns the context into JSON safe data.
 *
 * @package Expansa\Log\Formatters
 */
abstract class AbstractFormatter implements Formatter
{
    /**
     * Nesting level after which arrays and previous exceptions are cut.
     */
    protected const int MAX_DEPTH = 5;

    protected const int JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
        | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Convert a value into scalars and arrays: exceptions, dates, enums and objects become readable data.
     *
     * @param mixed $value
     * @param int   $depth
     * @return mixed
     */
    protected function normalize(mixed $value, int $depth = 0): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($depth >= static::MAX_DEPTH) {
            return '...';
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalize($item, $depth + 1);
            }

            return $value;
        }

        return match (true) {
            $value instanceof Throwable         => $this->normalizeException($value, $depth),
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::RFC3339),
            $value instanceof UnitEnum          => $value::class . '::' . $value->name,
            $value instanceof JsonSerializable  => $this->normalize($value->jsonSerialize(), $depth + 1),
            $value instanceof Stringable        => (string) $value,
            is_object($value)                   => '[object ' . $value::class . ']',
            default                             => '[resource ' . get_resource_type($value) . ']',
        };
    }

    /**
     * Describe an exception with its location and the previous exceptions.
     *
     * @param Throwable $e
     * @param int       $depth
     * @return array
     */
    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        $data = [
            'class'   => $e::class,
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ];

        if ($e->getPrevious() !== null) {
            $data['previous'] = $this->normalize($e->getPrevious(), $depth + 1);
        }

        return $data;
    }

    /**
     * Encode normalized data, never failing on invalid UTF-8 or unsupported values.
     *
     * @param mixed $data
     * @return string
     */
    protected function toJson(mixed $data): string
    {
        return (string) json_encode($data, static::JSON_FLAGS);
    }
}
