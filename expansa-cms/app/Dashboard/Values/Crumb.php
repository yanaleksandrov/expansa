<?php

declare(strict_types=1);

namespace App\Dashboard\Values;

/**
 * Item of the breadcrumbs of a dashboard page, see parts/breadcrumbs: a link with the URL, plain text without it.
 *
 * @package App\Dashboard
 */
final readonly class Crumb
{
    public function __construct(

        /**
         * Text of the item.
         */
        public string $label,

        /**
         * URL of the item, empty for plain text.
         */
        public string $url = '',

        /**
         * HTML attributes of the item.
         *
         * @var array<string, mixed>
         */
        public array $attributes = [],
    ) {}
}
