<?php

declare(strict_types=1);

namespace App\Support\Values;

/**
 * Field changed by an edit in an Activity line: what the value was and what it became.
 *
 * @package App\Support
 */
final readonly class Change
{
    public function __construct(

        /**
         * Human name of the field.
         */
        public string $field,

        /**
         * Value before the edit, empty for none.
         */
        public string $from = '',

        /**
         * Value after the edit, empty for none.
         */
        public string $to = '',
    ) {}
}
