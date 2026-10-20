<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of the `plugin`, `extension` and `theme` cells: a plugin or a theme, installed or from the repository.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Extension implements Stringable
{
    public function __construct(

        /**
         * Name of the extension.
         */
        public string $title,

        /**
         * Short description.
         */
        public string $description = '',

        /**
         * URL of the screenshot.
         */
        public string $screenshot = '',

        /**
         * Authors, with links to their sites.
         *
         * @var Link[]
         */
        public array $authors = [],

        /**
         * Category in the repository.
         */
        public string $category = '',

        /**
         * Version of the extension.
         */
        public string $version = '',

        /**
         * Whether the extension is installed.
         */
        public bool $installed = false,

        /**
         * Whether the extension is active.
         */
        public bool $active = false,

        /**
         * Number of the installations, as shown: `300k+`.
         */
        public string $installations = '',

        /**
         * Date of the last update, formatted.
         */
        public string $date = '',

        /**
         * Average rating out of five.
         */
        public float $rating = 0.0,

        /**
         * Number of the reviews.
         */
        public int $reviews = 0,
    ) {}

    /**
     * Get the text the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->title;
    }
}
