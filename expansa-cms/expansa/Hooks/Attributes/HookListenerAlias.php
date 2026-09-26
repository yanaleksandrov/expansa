<?php

declare(strict_types=1);

namespace Expansa\Hooks\Attributes;

use Attribute;

/**
 * Gives an anonymous function a stable name so it can later be targeted by {@see \Expansa\Hooks\Manager::flush()}.
 */
#[Attribute(Attribute::TARGET_FUNCTION)]
final readonly class HookListenerAlias
{
    public function __construct(

        /**
         * The name used to identify the anonymous function when removing it as a listener.
         *
         * @var string
         */
        public string $anonymousFunctionName,
    ) {} // phpcs:ignore
}
