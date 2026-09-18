<?php

declare(strict_types=1);

namespace Expansa\Hooks\Attributes;

use Attribute;

/**
 * Sets the execution priority of a listener method discovered by {@see \Expansa\Hooks\Manager::configure()}.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class HookListenerPriority
{
    public function __construct(
        /**
         * The priority the listener should run with, in ascending order. See {@see \Expansa\Hooks\Priority}.
         *
         * @var int
         */
        public int $priority
    ) {} // phpcs:ignore
}
