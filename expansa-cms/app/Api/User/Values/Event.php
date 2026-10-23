<?php

declare(strict_types=1);

namespace App\Api\User\Values;

/**
 * Entry of the security log of a user, see Events::all().
 *
 * @package App\Api\User
 */
final readonly class Event
{
    public function __construct(

        /**
         * Human name of the event.
         */
        public string $label,

        /**
         * Phosphor icon of the event.
         */
        public string $icon = '',

        /**
         * Login of the administrator who did it for the user, empty for the user's own action.
         */
        public string $by = '',

        /**
         * Human name of the device.
         */
        public string $device = '',

        /**
         * IP address of the request.
         */
        public string $ip = '',

        /**
         * Database datetime of the event.
         */
        public string $at = '',

        /**
         * Date of the event, formatted.
         */
        public string $date = '',
    ) {}
}
