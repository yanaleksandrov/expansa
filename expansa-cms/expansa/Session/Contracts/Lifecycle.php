<?php

declare(strict_types=1);

namespace Expansa\Session\Contracts;

use Expansa\Session\Exceptions\AlreadyStarted;
use Expansa\Session\Exceptions\HeadersSent;
use Expansa\Session\Exceptions\NotStarted;
use RuntimeException;

/**
 * Session lifecycle: start, new id, save and delete.
 * Use it instead of session_start(), session_regenerate_id() and session_destroy().
 *
 * @package Expansa\Session
 */
interface Lifecycle
{
    public bool $started { get; }

    /**
     * Session id, empty before the start.
     *
     * @var string
     */
    public string $id { get; }

    /**
     * Session name, the cookie name for the native session.
     *
     * @var string
     */
    public string $name { get; }

    /**
     * Start the session.
     *
     * @return void
     * @throws AlreadyStarted   If the session is already started.
     * @throws HeadersSent      If the output has already started.
     * @throws RuntimeException If PHP can't start the session.
     */
    public function start(): void;

    /**
     * Move the data to a new session id, the old session is deleted.
     *
     * @return void
     * @throws NotStarted       If the session is not started.
     * @throws HeadersSent      If the output has already started.
     * @throws RuntimeException If PHP can't regenerate the id.
     */
    public function regenerateId(): void;

    /**
     * Forget the data and delete the stored session with its cookie.
     *
     * @return void
     * @throws RuntimeException If PHP can't delete the session.
     */
    public function delete(): void;

    /**
     * Save the data and close the session; PHP also does it at the end of the request.
     *
     * @return void
     */
    public function save(): void;
}
