<?php

declare(strict_types=1);

namespace App\Api\User\Values;

/**
 * Device a user is signed in on, a card of the profile, see Sessions::all().
 *
 * @package App\Api\User
 */
final readonly class Session
{
    public function __construct(

        /**
         * Row ID of the session.
         */
        public int $id,

        /**
         * Human name of the device: "Chrome, Windows".
         */
        public string $device,

        /**
         * IP address of the last request.
         */
        public string $ip = '',

        /**
         * Date of the last request, formatted.
         */
        public string $active = '',

        /**
         * Whether the device is a phone.
         */
        public bool $mobile = false,

        /**
         * Whether it is the device of the current request.
         */
        public bool $current = false,
    ) {}
}
