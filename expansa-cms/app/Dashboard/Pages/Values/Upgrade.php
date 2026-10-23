<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

/**
 * Past update in the history of the updates page.
 *
 * @package App\Dashboard
 */
final readonly class Upgrade
{
    public function __construct(

        /**
         * Name of the core, the extension or the language.
         */
        public string $name,

        /**
         * Version before the update.
         */
        public string $from,

        /**
         * Version after the update.
         */
        public string $to,

        /**
         * Date of the update, formatted.
         */
        public string $date,

        /**
         * Whether the update succeeded; a failed one was rolled back.
         */
        public bool $succeeded = true,
    ) {}
}
