<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

/**
 * Request a button of a table posts to the API, e.g. emptying the trash.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Action
{
    public function __construct(

        /**
         * API route, e.g. `post/empty-trash`.
         */
        public string $endpoint,

        /**
         * Fields posted with the request.
         *
         * @var array<string, scalar>
         */
        public array $data = [],
    ) {}
}
