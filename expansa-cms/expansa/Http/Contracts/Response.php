<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

/**
 * HTTP response that can be sent to the client.
 *
 * @package Expansa\Http
 */
interface Response
{
    /**
     * Response body.
     */
    public string $content { get; }

    /**
     * HTTP status code.
     */
    public int $statusCode { get; }

    /**
     * Headers, name => value.
     *
     * @var array<string, string>
     */
    public array $headers { get; }

    /**
     * Send the status, headers and body.
     *
     * @return static
     */
    public function send(): static;
}
