<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION)]
final class HookListenerAlias
{
    public function __construct(
        public string $anonymousFunctionName,
    ) {} // phpcs:ignore
}
