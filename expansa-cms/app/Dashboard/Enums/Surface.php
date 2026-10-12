<?php

declare(strict_types=1);

namespace App\Dashboard\Enums;

/**
 * Background of the page area of the dashboard: the value is the modifier class `expansa-main--{value}`,
 * its color comes from the theme tokens.
 *
 * @package App\Dashboard
 */
enum Surface: string
{
    /**
     * White, for lists and forms.
     */
    case Plain = 'plain';

    /**
     * Gray as the side menus, for editors and builders with white panels.
     */
    case Muted = 'muted';

    /**
     * Translucent tint, for previews and workspaces.
     */
    case Canvas = 'canvas';
}
