<?php

declare(strict_types=1);

namespace App\Dashboard\Pages\Values;

use App\Post\Taxonomy;

/**
 * Select of the terms of a taxonomy in the post editor, see Pages\Post::terms().
 *
 * @package App\Dashboard
 */
final readonly class TermSelect
{
    public function __construct(

        /**
         * Taxonomy of the terms.
         */
        public Taxonomy $taxonomy,

        /**
         * Names of the terms to choose by ID, a dash per level in a hierarchical taxonomy; the chosen ones are among them.
         *
         * @var array<int, string>
         */
        public array $options = [],

        /**
         * IDs of the chosen terms.
         *
         * @var int[]
         */
        public array $selected = [],

        /**
         * Whether there are more terms than the options, so the select searches on the server.
         */
        public bool $search = false,

        /**
         * Whether the viewer may add a new term by its name.
         */
        public bool $canCreate = false,
    ) {}
}
