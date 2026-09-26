<?php

declare(strict_types=1);

namespace Expansa\Http\Exception;

use Expansa\Http\Contracts\Response;

class HttpResponseException extends \RuntimeException
{
    public function __construct(

        /**
         * Ready response to send instead of the regular handler result.
         */
        protected Response $response,
    ) {} // phpcs:ignore

    public function getResponse(): Response
    {
        return $this->response;
    }
}
