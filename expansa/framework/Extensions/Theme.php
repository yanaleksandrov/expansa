<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Extensions\Contracts\ExtensionSkeleton;
use Expansa\Extensions\Traits\ExtensionTraits;

abstract class Theme extends Extension implements ExtensionSkeleton
{
    use ExtensionTraits;

    public string $type = 'theme';
}
