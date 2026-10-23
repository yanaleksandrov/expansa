<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

/**
 * Bulk actions of a table, applied to the checked rows, see Table::bulk().
 *
 * @package Expansa\Builders\Table
 */
final readonly class Bulk
{
    public function __construct(

        /**
         * API route the IDs of the rows and the action are posted to.
         */
        public string $endpoint,

        /**
         * Labels of the actions by value, or groups of the select: `label` and `options`.
         *
         * @var array<string, mixed>
         */
        public array $actions,

        /**
         * Actions that ask first.
         *
         * @var string[]
         */
        public array $confirm = [],

        /**
         * Actions that need the current password.
         *
         * @var string[]
         */
        public array $password = [],

        /**
         * Fields posted with the IDs.
         *
         * @var array<string, scalar>
         */
        public array $data = [],
    ) {}
}
