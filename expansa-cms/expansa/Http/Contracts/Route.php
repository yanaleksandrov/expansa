<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

/**
 * Route matched for the request. Implemented by the router and set on Request::$route.
 *
 * @package Expansa\Http
 */
interface Route
{
    /**
     * Route pattern, e.g. "/posts/(\d+)".
     */
    public string $pattern { get; }

    /**
     * Values captured from the URI.
     *
     * @var array<array-key, string>
     */
    public array $parameters { get; }
}
