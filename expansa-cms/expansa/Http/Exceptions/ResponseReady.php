<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Expansa\Http\Response;
use RuntimeException;

/**
 * Thrown to stop handling and send a ready response instead of the handler result.
 *
 * @package Expansa\Http
 */
final class ResponseReady extends RuntimeException
{
    public function __construct(

        /**
         * Ready response to send instead of the regular handler result.
         */
        public readonly Response $response,
    ) {
        parent::__construct('Response is ready to send.');
    }
}
