<?php

declare(strict_types=1);

namespace Expansa\Application;

use Expansa\Container\Container;
use Expansa\Patterns\Singleton;

class App
{
    use Singleton;

    public function __construct(
        protected Container $container = new Container()
    )
    {
        $this->registerServices();
    }

    public static function configure(): self {

    }

    public function api() {

    }

    public function logging() {

    }

    public function routes() {

    }

    public function extensions() {

    }

    public function listeners() {

    }

    public function database() {

    }

    public function commands() {

    }

    public function migrations() {

    }

    public function views() {

    }

    public function scheduler() {

    }

    public function registerServices(): void
    {
        $this->container->set('database', new Database());
        $this->container->set('router', new Router());
    }

    public function get($name)
    {
        return $this->container->get($name);
    }
}
