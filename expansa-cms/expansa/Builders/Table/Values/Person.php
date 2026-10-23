<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of the `author` and `user` cells: the name of a person linking to the profile, with the avatar initials
 * or the login and the email below.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Person implements Stringable
{
    public function __construct(

        /**
         * Name shown, nothing is shown for an empty one.
         */
        public string $name,

        /**
         * URL of the profile, empty for a person without one, e.g. a guest author.
         */
        public string $url = '',

        /**
         * Email shown below the name.
         */
        public string $email = '',

        /**
         * Login shown before the email.
         */
        public string $login = '',

        /**
         * Letters of the avatar.
         */
        public string $initials = '',

        /**
         * Color of the avatar, a name of the palette: `blue`, `green`.
         */
        public string $color = 'blue',
    ) {}

    /**
     * Get the text the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
