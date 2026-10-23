<?php

declare(strict_types=1);

namespace App\Support\Values;

use App\Support\SiteHealth;

/**
 * Section of the site-health page with its checks; the status is the worst status of the checks.
 *
 * @package App\Support
 */
final class Section
{
    /**
     * Worst status of the checks, see SiteHealth::status().
     */
    public string $status {
        get => SiteHealth::status($this->checks);
    }

    public function __construct(

        /**
         * Key of the section, the accordion item open on load is found by it.
         */
        public readonly string $id,

        /**
         * Title, HTML of t().
         */
        public readonly string $title,

        /**
         * Classes of the icon.
         */
        public readonly string $icon,

        /**
         * Checks of the section.
         *
         * @var Check[]
         */
        public readonly array $checks = [],
    ) {}
}
