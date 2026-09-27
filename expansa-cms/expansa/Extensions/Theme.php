<?php

declare(strict_types=1);

namespace Expansa\Extensions;

use Expansa\Extensions\Internal\AbstractExtension;
use Expansa\Extensions\Contracts\Extension;

/**
 * Base of a theme: `themes/<slug>/index.php` returns an anonymous subclass.
 *
 * @package Expansa\Extensions
 */
abstract class Theme extends AbstractExtension implements Extension
{
    public string $type = 'theme';
}
