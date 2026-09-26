<?php

declare(strict_types=1);

namespace Expansa\Session\Contracts;

use Expansa\Session\Exceptions\SessionException;

/**
 * Session lifecycle: start, new id, save and delete.
 * Use it instead of session_start(), session_regenerate_id() and session_destroy().
 *
 * @package Expansa\Session
 */
interface Manager
{
    public bool $started { get; }

    /**
     * Session id, empty before the start.
     */
    public string $id { get; }

    /**
     * Session name, the cookie name for the native session.
     */
    public string $name { get; }

    /**
     * Start the session.
     *
     * @return void
     * @throws SessionException If the session can't be started.
     */
    public function start(): void;

    /**
     * Move the data to a new session id, the old session is deleted.
     *
     * @return void
     * @throws SessionException If the id can't be regenerated.
     */
    public function regenerateId(): void;

    /**
     * Forget the data and delete the stored session with its cookie.
     *
     * @return void
     * @throws SessionException If the session can't be deleted.
     */
    public function delete(): void;

    /**
     * Save the data and close the session; PHP also does it at the end of the request.
     *
     * @return void
     */
    public function save(): void;
}
