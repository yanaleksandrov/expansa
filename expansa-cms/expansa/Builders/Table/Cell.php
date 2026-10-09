<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

/**
 * Column of a dashboard table, described with fluent setters:
 * `$this->cell('date')->title(t('Date'))->fixedWidth('9rem')->view('date')`.
 *
 * @package Expansa\Builders\Table
 */
final class Cell
{
    public function __construct(

        /**
         * Key of the column value in a row.
         */
        public string $key = '',

        /**
         * Column title, may contain HTML.
         */
        public string $title = '',

        /**
         * View that renders the cells of the column.
         */
        public string $view = '',

        /**
         * Whether the column can be sorted.
         */
        public bool $sortable = false,

        /**
         * Column width, a CSS length; the minimum one for a flexible column.
         */
        public string $width = '',

        /**
         * Whether the column grows from $width to take free space.
         */
        public bool $flexible = false,

        /**
         * Whether the column values can be searched.
         */
        public bool $searchable = false,

        /**
         * HTML attributes of the cell wrapper.
         */
        public array $attributes = [],
    ) {}

    /**
     * Set the column title.
     *
     * @param string $title
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the HTML attributes of the cell wrapper.
     *
     * @param array $attributes
     * @return static
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Make the column sortable.
     *
     * @return static
     */
    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    /**
     * Make the column searchable.
     *
     * @return static
     */
    public function searchable(): static
    {
        $this->searchable = true;

        return $this;
    }

    /**
     * Set a fixed column width.
     *
     * @param string $width CSS length, e.g. `9rem`.
     * @return static
     */
    public function fixedWidth(string $width): static
    {
        $this->flexible = false;
        $this->width    = $width;

        return $this;
    }

    /**
     * Set the minimum width of a column that takes free space.
     *
     * @param string $width CSS length, e.g. `6rem`.
     * @return static
     */
    public function flexibleWidth(string $width): static
    {
        $this->flexible = true;
        $this->width    = $width;

        return $this;
    }

    /**
     * Set the view of the cells: a file path, or a kind added to the default view (`date` → `<default>-date`).
     *
     * @param string $view
     * @return static
     */
    public function view(string $view): static
    {
        $this->view = file_exists($view) ? $view : "{$this->view}-$view";

        return $this;
    }
}
