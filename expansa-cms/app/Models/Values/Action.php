<?php

declare(strict_types=1);

namespace App\Models\Values;

/**
 * Quick moderation action of a comment, see Comment::moderationActions().
 *
 * @package App\Models
 */
final readonly class Action
{
    public function __construct(

        /**
         * Name of the action.
         */
        public string $label,

        /**
         * Classes of the icon.
         */
        public string $icon,
    ) {}
}
