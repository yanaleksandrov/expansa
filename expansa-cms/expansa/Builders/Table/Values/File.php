<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `file` cell: the thumbnail of an image or the icon of the type, the name linking to the file,
 * the size and the dimensions below.
 *
 * @package Expansa\Builders\Table
 */
final readonly class File implements Stringable
{
    public function __construct(

        /**
         * Name of the file.
         */
        public string $name,

        /**
         * URL of the file, the name is plain text without it.
         */
        public string $url = '',

        /**
         * URL of the thumbnail, the icon is shown without it.
         */
        public string $thumbnail = '',

        /**
         * Classes of the icon of the type.
         */
        public string $icon = 'ph ph-file',

        /**
         * Formatted size, e.g. "1.2 MB".
         */
        public string $size = '',

        /**
         * Width of an image in pixels, 0 for other files.
         */
        public int $width = 0,

        /**
         * Height of an image in pixels, 0 for other files.
         */
        public int $height = 0,
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
