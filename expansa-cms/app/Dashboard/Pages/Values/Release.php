<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

/**
 * Release of the core available on the updates page, with its notes.
 *
 * @package App\Dashboard
 */
final readonly class Release
{
    public function __construct(

        /**
         * Installed version.
         */
        public string $current,

        /**
         * Latest version.
         */
        public string $latest,

        /**
         * Kind of the update: `major`, `minor` or `patch`.
         */
        public string $type = 'patch',

        /**
         * Release date, formatted.
         */
        public string $date = '',

        /**
         * Download size, formatted.
         */
        public string $size = '',

        /**
         * What is new, HTML of t().
         *
         * @var string[]
         */
        public array $notes = [],
    ) {}
}
