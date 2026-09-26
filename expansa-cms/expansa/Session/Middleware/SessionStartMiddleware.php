<?php

declare(strict_types=1);

namespace Expansa\Session\Middleware;

use Expansa\Session\Contracts\MiddlewareInterface;
use Expansa\Session\Contracts\RequestHandlerInterface;
use Expansa\Session\Contracts\ResponseInterface;
use Expansa\Session\Contracts\ServerRequestInterface;
use Expansa\Session\Contracts\SessionManagerInterface;

final class SessionStartMiddleware implements MiddlewareInterface
{
    public function __construct(

        /**
         * Session started before the handler (unless already started) and saved after it.
         */
        private SessionManagerInterface $session,
    ) {} // phpcs:ignore

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $response = $handler->handle($request);
        $this->session->save();

        return $response;
    }
}
