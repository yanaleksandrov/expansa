<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Ai\Draft;
use Expansa\Ai\Session;
use Expansa\Patterns\Facade;

/**
 * Static access to AI plugin generation.
 * Swap in a configured manager before the first call.
 *
 * @method static Draft create(string $input, ?Closure $progress = null)
 * @method static Draft clarify(Session $session, string $answer, ?Closure $progress = null)
 * @method static void  swap(object $instance)
 * @method static void  forgetResolved()
 */
final class Ai extends Facade
{
    /**
     * Returns the manager instance used by this facade.
     * Configure the instance with the inherited `swap()` method.
     *
     * @return class-string Manager class for this facade
     */
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Ai\Manager::class;
    }
}
