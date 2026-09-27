<?php

declare(strict_types=1);

namespace Expansa\Http\Contracts;

/**
 * Session storage of the previous request input, for refilling a form after a failed submit.
 * Implemented outside Http and set on Request::$session.
 *
 * @package Expansa\Http
 */
interface Session
{
    /**
     * Input value flashed by the previous request.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getOldInput(string $key, mixed $default = null): mixed;

    /**
     * Flash the input for the next request, replacing the flashed one.
     *
     * @param array<array-key, mixed> $input
     * @return void
     */
    public function setOldInput(array $input): void;
}
