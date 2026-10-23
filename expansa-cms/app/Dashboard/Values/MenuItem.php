<?php

declare(strict_types=1);

namespace App\Dashboard\Values;

/**
 * Row of the menu structure in screens/nav-editor, rendered by the server until the editor draws its own:
 * the list keeps its height while the page loads.
 *
 * @package App\Dashboard
 */
final readonly class MenuItem
{
    public function __construct(

        /**
         * Text of the row: the label of the item, the title of its post or its URL.
         */
        public string $label,

        /**
         * Level of the item, 0 for the top one.
         */
        public int $depth = 0,

        /**
         * Icon classes of the item.
         */
        public string $icon = '',

        /**
         * Label of the post type of a post item, empty for another one.
         */
        public string $type = '',

        /**
         * Label of the type of an item that is not a post: a custom link, a heading and so on.
         */
        public string $kind = '',

        /**
         * Why the site leaves the item out: `deleted` or `hidden` (an unpublished post), empty if shown.
         */
        public string $problem = '',
    ) {}
}
