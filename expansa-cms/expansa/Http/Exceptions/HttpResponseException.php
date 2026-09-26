<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Expansa\Http\Contracts\Response;

class HttpResponseException extends \RuntimeException
{
    public function __construct(

        /**
         * Ready response to send instead of the regular handler result.
         */
        public readonly Response $response,
    ) {}
}
