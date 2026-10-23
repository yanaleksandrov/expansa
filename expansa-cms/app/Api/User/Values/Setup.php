<?php

declare(strict_types=1);

namespace App\Api\User\Values;

/**
 * Secret of two-factor authentication being set up, see TwoFactor::setup().
 *
 * @package App\Api\User
 */
final readonly class Setup
{
    public function __construct(

        /**
         * Secret for typing, in groups of four.
         */
        public string $secret,

        /**
         * QR code of the secret as SVG.
         */
        public string $qr,
    ) {}
}
