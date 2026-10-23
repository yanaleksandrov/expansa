<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `device` cell: the icon and the name of the browser and the system, the IP address below
 * and the "This Device" badge on the device of the viewer.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Device implements Stringable
{
    public function __construct(

        /**
         * Browser and system, e.g. "Chrome on Windows".
         */
        public string $name,

        /**
         * IP address.
         */
        public string $ip = '',

        /**
         * Classes of the icon, e.g. `ph ph-device-mobile` of a phone.
         */
        public string $icon = 'ph ph-desktop',

        /**
         * Whether it is the device of the viewer.
         */
        public bool $current = false,
    ) {}

    /**
     * Get the text the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
