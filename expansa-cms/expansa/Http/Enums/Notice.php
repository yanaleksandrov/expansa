<?php

declare(strict_types=1);

namespace Expansa\Http\Enums;

/**
 * Kind of a notification of the `notify` fragment: its icon and color in the dashboard.
 *
 * @package Expansa\Http
 */
enum Notice: string
{
    case Info    = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error   = 'error';
    case Loading = 'loading';
}
