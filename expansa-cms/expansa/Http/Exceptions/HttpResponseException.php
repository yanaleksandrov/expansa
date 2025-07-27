<?php

declare(strict_types=1);

namespace Expansa\Http\Exceptions;

use Expansa\Http\Contracts\Response;

class HttpResponseException extends \RuntimeException
{
    public function __construct(
        protected Response $response
    ) {} // phpcs:ignore

    public function getResponse(): Response
    {
        return $this->response;
    }
}
