<?php

declare(strict_types=1);

namespace Expansa\Ai\Validators;

use Expansa\Ai\Contracts\Validator;

/**
 * Runs several validators and joins their errors in order.
 */
final class Chain implements Validator
{
    /**
     * Stores the validators to run.
     */
    public function __construct(

        /**
         * Validators in the order their errors are reported.
         *
         * @var Validator[]
         */
        public readonly array $validators,
    ) {}

    /**
     * Collects errors from every validator.
     *
     * @param array<string, string> $files Relative paths mapped to source
     * @return string[] Validation errors, or an empty array
     */
    public function validate(array $files): array
    {
        return array_merge(...array_map(fn (Validator $validator): array => $validator->validate($files), $this->validators));
    }
}
