<?php

declare(strict_types=1);

namespace App\Support\Values;

use App\Support\SiteHealth;

/**
 * Check of the site-health page: what is checked, its current value, the status and the hint for a failed one.
 * Label, value and hint are HTML.
 *
 * @package App\Support
 */
final readonly class Check
{
    public function __construct(

        /**
         * What is checked.
         */
        public string $label,

        /**
         * Current value.
         */
        public string $value,

        /**
         * Status: SiteHealth::GOOD, RECOMMENDED or CRITICAL.
         */
        public string $status = SiteHealth::GOOD,

        /**
         * What to do, empty for a passed check.
         */
        public string $hint = '',
    ) {}
}
