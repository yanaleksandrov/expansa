<?php

declare(strict_types=1);

use Expansa\Patterns\Facade;

// run: php tests/Facade.php
const EX_PATH = __DIR__ . '/../expansa-cms/';

require_once EX_PATH . 'autoload.php';

$failures = 0;

function check(string $title, bool $condition): void
{
    global $failures;

    echo ($condition ? 'ok   ' : 'FAIL ') . $title . PHP_EOL;

    $failures += $condition ? 0 : 1;
}

final class Counter
{
    public int $value = 0;

    public function increment(): int
    {
        return ++$this->value;
    }
}

final class CounterFacade extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return Counter::class;
    }
}

final class OtherFacade extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return ArrayObject::class;
    }
}

CounterFacade::increment();
check('a facade keeps one instance', CounterFacade::increment() === 2);

CounterFacade::clearResolved();
check('clearResolved() gives a fresh instance', CounterFacade::increment() === 1);

$double        = new Counter();
$double->value = 100;
CounterFacade::swap($double);
check('swap() puts the given instance behind the facade', CounterFacade::increment() === 101 && $double->value === 101);

OtherFacade::count();
Facade::clearResolved();
check('Facade::clearResolved() drops every instance', CounterFacade::increment() === 1);

exit($failures > 0 ? 1 : 0);
