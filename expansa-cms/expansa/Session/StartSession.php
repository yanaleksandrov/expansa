<?php

declare(strict_types=1);

namespace Expansa\Session;

use Expansa\Session\Contracts\Manager;
use Expansa\Session\Contracts\MiddlewareInterface;
use Expansa\Session\Contracts\RequestHandlerInterface;
use Expansa\Session\Contracts\ResponseInterface;
use Expansa\Session\Contracts\ServerRequestInterface;

/**
 * PSR-15 middleware: starts the session before the handler, unless started, and saves it after.
 *
 * @package Expansa\Session
 */
final class StartSession implements MiddlewareInterface
{
    public function __construct(

        /**
         * Session started before the handler (unless already started) and saved after it.
         */
        private Manager $session,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->session->started) {
            $this->session->start();
        }

        $response = $handler->handle($request);
        $this->session->save();

        return $response;
    }
}
