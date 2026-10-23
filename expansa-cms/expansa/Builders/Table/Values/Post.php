<?php

declare(strict_types=1);

namespace Expansa\Builders\Table\Values;

use Stringable;

/**
 * Value of a `post` cell: the post a comment answers, linking to its editor, with the link to the page
 * and the number of the approved comments.
 *
 * @package Expansa\Builders\Table
 */
final readonly class Post implements Stringable
{
    public function __construct(

        /**
         * Title of the post.
         */
        public string $title,

        /**
         * URL of the editor of the post.
         */
        public string $edit,

        /**
         * URL of the page of the post, empty for an unpublished one.
         */
        public string $view = '',

        /**
         * Number of the approved comments.
         */
        public int $comments = 0,
    ) {}

    /**
     * Get the text the rows are sorted and filtered by, see Table::sortValue().
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->title;
    }
}
