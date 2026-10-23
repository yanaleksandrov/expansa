<?php

declare(strict_types=1);

namespace Expansa\Builders\Form\Values;

/**
 * Tab of a form level, built by Data from an item of `tabs`: the menu button and the panel.
 *
 * @package Expansa\Builders\Form
 */
final readonly class Tab
{
    public function __construct(

        /**
         * Reactive key of the tab for the `tab` component, `tab` by default.
         */
        public string $prop = 'tab',

        /**
         * Label of the menu button, may contain markup.
         */
        public string $label = '',

        /**
         * Classes of the icon of the button.
         */
        public string $icon = '',

        /**
         * Heading of the panel.
         */
        public string $caption = '',

        /**
         * Text under the heading, may contain markup.
         */
        public string $description = '',

        /**
         * Rendered nested fields.
         */
        public string $content = '',

        /**
         * Expression of the `u-show` condition of the tab, empty for none.
         */
        public string $condition = '',

        /**
         * Whether the conditions show the tab when the page opens.
         */
        public bool $visible = true,

        /**
         * Whether the tab is shown when the page opens.
         */
        public bool $active = false,
    ) {}
}
