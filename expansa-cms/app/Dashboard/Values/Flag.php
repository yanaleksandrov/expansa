<?php

declare(strict_types=1);

namespace App\Dashboard\Values;

/**
 * Icon of a setting that differs from its default, shown in a row of a list: a menu item that opens
 * in a new tab, is shown to logged-in users only and so on.
 *
 * @package App\Dashboard
 */
final readonly class Flag
{
    public function __construct(

        /**
         * Icon classes of the setting.
         */
        public string $icon,

        /**
         * Name and value of the setting, the tooltip of the icon.
         */
        public string $title,
    ) {}
}
