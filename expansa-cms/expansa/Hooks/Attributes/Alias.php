<?php

declare(strict_types=1);

namespace Expansa\Hooks\Attributes;

use Attribute;

/**
 * Stable name of a closure listener, flush() forgets the closure by it:
 * `Hook::add('postSaved', #[Alias('notifyAuthor')] fn ($post) => ...)`.
 *
 * @package Expansa\Hooks
 */
#[Attribute(Attribute::TARGET_FUNCTION)]
final readonly class Alias
{
    public function __construct(

        /**
         * Name the closure is flushed by.
         */
        public string $name,
    ) {}
}
