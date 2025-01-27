<?php

declare(strict_types=1);

namespace Dashboard\Table\Traits;

trait Cell
{
    /**
     * Title of column.
     *
     * @var string
     */
    public string $title = '';

    /**
     * Path to get view for render column cell.
     *
     * @var string
     */
    public string $view = 'table/cell';

    /**
     * Column is sortable.
     *
     * @var bool
     */
    public bool $sortable = false;

    /**
     * Column default sort ordering.
     *
     * @var string
     */
    public string $sortOrder = 'DESC';

    /**
     * Min column width.
     *
     * @var string
     */
    public string $width = '';

    /**
     * Column width is flexible.
     *
     * @var bool
     */
    public bool $flexible = false;

    /**
     * Column is searchable.
     *
     * @var bool
     */
    public bool $searchable = false;

    /**
     * Cell wrapper html attributes list.
     *
     * @var array
     */
    public array $attributes = [];

    /**
     * Column constructor.
     *
     * @param string $key Unique column key.
     */
    public function __construct(
        public string $key = ''
    )
    {
        return $this;
    }
}
