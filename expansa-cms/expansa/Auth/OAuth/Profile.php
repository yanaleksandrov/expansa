<?php

declare(strict_types=1);

namespace Expansa\Auth\OAuth;

/**
 * The user as an external provider vouches for them. The pair of provider and ID identifies
 * the account; the email is trusted only with $emailVerified, and even then an existing account
 * with the same email should be linked by its owner, not automatically.
 */
final readonly class Profile
{
    public function __construct(

        /**
         * Name of the provider, e.g. `google`.
         */
        public string $provider,

        /**
         * Stable account ID at the provider: the OpenID `sub`, the GitHub user ID.
         */
        public string $id,

        /**
         * Email address, null if the provider shared none.
         */
        public ?string $email = null,

        /**
         * Whether the provider confirmed that the user owns the email.
         */
        public bool $emailVerified = false,

        /**
         * Display name, may be empty.
         */
        public string $name = '',

        /**
         * Avatar URL.
         */
        public ?string $avatar = null,

        /**
         * Claims or the user object as the provider returned them.
         *
         * @var array<string, mixed>
         */
        public array $raw = [],
    ) {}
}
