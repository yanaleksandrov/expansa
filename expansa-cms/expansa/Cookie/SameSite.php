<?php

declare(strict_types=1);

namespace Expansa\Cookie;

/**
 * SameSite cookie attribute: when the browser sends the cookie with cross-site requests.
 *
 * @package Expansa\Cookie
 */
enum SameSite: string
{
    /**
     * Sent with all requests, requires the secure attribute.
     */
    case None = 'none';

    /**
     * Sent with top-level cross-site navigation, not with subrequests (images, iframes).
     */
    case Lax = 'lax';

    /**
     * Never sent with cross-site requests.
     */
    case Strict = 'strict';
}
