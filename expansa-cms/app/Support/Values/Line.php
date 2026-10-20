<?php

declare(strict_types=1);

namespace App\Support\Values;

use App\Support\Dates;

/**
 * Line of an Activity tab, see Activity::timeline(): an event of the log or a line the editor adds,
 * such as the creation or a comment of a post.
 *
 * @package App\Support
 */
final class Line
{
    /**
     * Time in ISO 8601 for the `datetime` attribute, empty without a time.
     */
    public string $datetime {
        get => $this->at !== '' ? date('c', (int) strtotime($this->at)) : '';
    }

    /**
     * Time formatted for the reader, empty without a time.
     */
    public string $date {
        get => $this->at !== '' ? Dates::format($this->at) : '';
    }

    public function __construct(

        /**
         * Classes of the icon.
         */
        public readonly string $icon,

        /**
         * What happened, plain text.
         */
        public readonly string $text,

        /**
         * Name of the user who did it, empty when unknown.
         */
        public readonly string $user = '',

        /**
         * Note under the text, e.g. the start of a comment or the device.
         */
        public readonly string $note = '',

        /**
         * Changed fields of an edit.
         *
         * @var Change[]
         */
        public readonly array $changes = [],

        /**
         * Time as `Y-m-d H:i:s`, the lines are sorted by it.
         */
        public readonly string $at = '',
    ) {}
}
