<?php

declare(strict_types=1);

namespace Expansa\Hooks;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class HookListenerPriority
{
	public function __construct(
		public int $priority
	) {}
}
