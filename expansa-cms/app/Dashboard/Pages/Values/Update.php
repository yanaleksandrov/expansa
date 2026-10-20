<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

/**
 * Available update of a plugin, a theme or a translation.
 *
 * @package App\Dashboard
 */
final readonly class Update
{
    public function __construct(

        /**
         * Name of the extension or the language.
         */
        public string $name,

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
         * Whether the update fixes a vulnerability.
         */
        public bool $security = false,

        /**
         * What is new, HTML of t().
         */
        public string $note = '',
    ) {}
}
