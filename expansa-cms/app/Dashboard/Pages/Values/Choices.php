<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

/**
 * Group of the options of a select, an `optgroup`: e.g. the field types of the field-groups page.
 *
 * @package App\Dashboard
 */
final readonly class Choices
{
    public function __construct(

        /**
         * Label of the group.
         */
        public string $label,

        /**
         * Labels of the options by value.
         *
         * @var array<string, string>
         */
        public array $options = [],
    ) {}
}
