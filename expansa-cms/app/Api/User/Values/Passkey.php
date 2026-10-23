<?php

declare(strict_types=1);

namespace App\Api\User\Values;

/**
 * Passkey of a user, a card of the profile, see Passkey::all().
 *
 * @package App\Api\User
 */
final readonly class Passkey
{
    public function __construct(

        /**
         * Row ID of the passkey.
         */
        public int $id,

        /**
         * Label given when it was added.
         */
        public string $name,

        /**
         * Date it was added, formatted.
         */
        public string $added = '',

        /**
         * Date of the last sign-in with it, formatted; empty for an unused one.
         */
        public string $used = '',

        /**
         * Whether the passkey is synced between devices.
         */
        public bool $synced = false,
    ) {}
}
