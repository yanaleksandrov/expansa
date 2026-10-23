<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

/**
 * OAuth provider of the sign-in page: the button that starts signing in with it.
 *
 * @package App\Dashboard
 */
final readonly class Provider
{
    public function __construct(

        /**
         * Key of the provider, also the name of its logo icon: `google`, `github`.
         */
        public string $id,

        /**
         * Name shown on the button.
         */
        public string $label,

        /**
         * URL that starts signing in.
         */
        public string $url,
    ) {}
}
