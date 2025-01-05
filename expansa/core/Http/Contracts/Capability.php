<?php

namespace Expansa\Http\Contracts;

/**
 * Capability interface declaring the known capabilities.
 * This is used as the authoritative source for which capabilities can be queried.
 *
 * @package Expansa\Http
 */
interface Capability
{
    /**
     * Support for SSL.
     *
     * @var string
     */
    public const SSL = 'ssl';

    /**
     * Collection of all capabilities supported in Requests.
     *
     * Note: this does not automatically mean that the capability will be supported for your chosen transport!
     *
     * @var array<string>
     */
    public const ALL = [self::SSL];
}
