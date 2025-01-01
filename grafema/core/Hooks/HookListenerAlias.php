<?php

declare(strict_types=1);

namespace Grafema\Hooks;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION)]
final class HookListenerAlias
{
	public function __construct(
		public string $anonymousFunctionName,
	) {}
}
