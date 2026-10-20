<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

/**
 * Accordion item of the updates page: the core, the updates of extensions, the history or the environment.
 *
 * @package App\Dashboard
 */
final readonly class Section
{
    public function __construct(

        /**
         * Key of the section: `core`, `history`, `environment` or of a kind of extensions.
         */
        public string $id,

        /**
         * Title, HTML of t().
         */
        public string $title,

        /**
         * Classes of the icon.
         */
        public string $icon,

        /**
         * Text of the badge after the title, e.g. the number of updates; none for an empty one.
         */
        public string $badge = '',

        /**
         * Updates of the section.
         *
         * @var Update[]
         */
        public array $items = [],
    ) {}
}
