<?php

declare(strict_types=1);

namespace App\Api\User\Values;

/**
 * Account of a user at a sign-in provider, a card of the profile, see Identities::all().
 *
 * @package App\Api\User
 */
final readonly class Identity
{
    public function __construct(

        /**
         * Row ID of the account.
         */
        public int $id,

        /**
         * Provider key, also the name of its Phosphor logo: `google`, `github`.
         */
        public string $provider,

        /**
         * Name of the provider.
         */
        public string $label,

        /**
         * Verified email of the account, empty without one.
         */
        public string $email = '',

        /**
         * Date it was connected, formatted without the time.
         */
        public string $connected = '',
    ) {}
}
