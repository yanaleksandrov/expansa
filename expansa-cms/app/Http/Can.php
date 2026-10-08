<?php

declare(strict_types=1);

namespace App\Http;

use Attribute;

/**
 * Permission an API method needs, checked by Kernel::dispatch() before the method runs:
 * `#[Can('manage_options')]`. A method without it is open to any signed-in user, e.g. the
 * own profile; RequireAuth decides about guests.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Can
{
    public function __construct(

        /**
         * Permission name of Access, e.g. `manage_options`.
         */
        public string $permission,
    ) {}
}
