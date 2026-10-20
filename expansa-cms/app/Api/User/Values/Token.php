<?php

declare(strict_types=1);

namespace App\Api\User\Values;

/**
 * Personal API token of a user, a card of the profile, see Tokens::all().
 *
 * @package App\Api\User
 */
final readonly class Token
{
    public function __construct(

        /**
         * Row ID of the token.
         */
        public int $id,

        /**
         * Label given when it was created.
         */
        public string $name,

        /**
         * Readable part of the token: `exp_<prefix>_…`.
         */
        public string $prefix,

        /**
         * Permissions the token acts with.
         *
         * @var string[]
         */
        public array $scopes = [],

        /**
         * Date of the last use, empty for an unused token.
         */
        public string $used = '',

        /**
         * Date it expires, empty for a token without an end.
         */
        public string $expires = '',
    ) {}
}
