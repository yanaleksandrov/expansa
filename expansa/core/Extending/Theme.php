<?php

declare(strict_types=1);

namespace Expansa\Extending;

use Expansa\Extending\Contracts\ExtensionSkeleton;
use Expansa\Extending\Traits\ExtensionTraits;

abstract class Theme extends Extension implements ExtensionSkeleton
{
    use ExtensionTraits;

    public string $type = 'theme';
}
