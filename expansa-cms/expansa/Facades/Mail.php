<?php

declare(strict_types=1);

namespace Expansa\Facades;

use Closure;
use Expansa\Mail\Mailer;
use Expansa\Patterns\Facade;

/**
 * Mail facade: `Mail::send($to, $subject, $body)` or `Mail::to($to)->subject(...)->message(...)->send()`.
 *
 * @method static void   configure(?Closure $setup = null)
 * @method static Mailer to(string $email)
 * @method static bool   send(string $to, string $subject, string $body, array $attachments = [])
 */
class Mail extends Facade
{
    protected static function getStaticClassAccessor(): string
    {
        return \Expansa\Mail\Manager::class;
    }
}
