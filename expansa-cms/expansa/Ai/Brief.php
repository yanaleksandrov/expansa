<?php

declare(strict_types=1);

namespace Expansa\Ai;

/**
 * Everything a generator needs for one generation or repair attempt.
 */
final class Brief
{
    /**
     * Stores the request, its approved specification, and feedback from the previous attempt.
     */
    public function __construct(

        /**
         * User's natural language requirements with clarification answers.
         *
         * @var string
         */
        public readonly string $request,

        /**
         * Relevant CMS APIs, hooks, and events.
         *
         * @var string
         */
        public readonly string $context,

        /**
         * Approved extension behavior and acceptance criteria.
         *
         * @var string
         */
        public readonly string $specification,

        /**
         * Validation errors of the previous attempt, empty for the first one.
         *
         * @var string[]
         */
        public readonly array $errors = [],

        /**
         * Files of the previous attempt keyed by relative paths, empty for the first one.
         *
         * @var array<string, string>
         */
        public readonly array $files = [],

        /**
         * PHP version and extensions the generated code may use.
         *
         * @var Platform
         */
        public readonly Platform $platform = new Platform(),
    ) {}
}
