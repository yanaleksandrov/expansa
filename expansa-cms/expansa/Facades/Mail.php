<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Expansa\Mail\Mailer;
use Expansa\Patterns\Facade;

/**
 * Provides static access to the Mail subsystem.
 *
 * @method static Mailer to(string $email)
 */
class Mail extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return 'Expansa\Mail\Mailer';
    }
}
