<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

class Cell
{
    public function __construct(

        /**
         * Unique column key.
         */
        public string $key = '',

        /**
         * Title of column.
         */
        public string $title = '',

        /**
         * Path to get view for render column cell.
         */
        public string $view = 'table/cell',

        /**
         * Column is sortable.
         */
        public bool $sortable = false,

        /**
         * Min column width.
         */
        public string $width = '',

        /**
         * Column width is flexible.
         */
        public bool $flexible = false,

        /**
         * Column is searchable.
         */
        public bool $searchable = false,

        /**
         * Cell wrapper HTML attributes list.
         */
        public array $attributes = [],
    ) {} // phpcs:ignore

    /**
     * Set column title.
     *
     * @param string $title
     * @return Cell
     */
    public function title(string $title): Cell
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set column title.
     *
     * @param array $attributes
     * @return Cell
     */
    public function attributes(array $attributes): Cell
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Make column sortable.
     *
     * @return Cell
     */
    public function sortable(): Cell
    {
        $this->sortable = true;

        return $this;
    }

    /**
     * Make column searchable.
     *
     * @return Cell
     */
    public function searchable(): Cell
    {
        $this->searchable = true;

        return $this;
    }

    /**
     * Set column width.
     *
     * @param string $width
     * @return Cell
     */
    public function fixedWidth(string $width): Cell
    {
        $this->flexible = false;
        $this->width    = $width;

        return $this;
    }

    /**
     * Set column width flexible.
     *
     * @param string $width
     * @return Cell
     */
    public function flexibleWidth(string $width): Cell
    {
        $this->flexible = true;
        $this->width    = $width;

        return $this;
    }

    /**
     * Get view template.
     *
     * @param string $filepath
     * @return Cell
     */
    public function view(string $filepath): Cell
    {
        if (file_exists($filepath)) {
            $this->view = $filepath;
        } else {
            $this->view = sprintf('%s-%s', $this->view, $filepath);
        }

        return $this;
    }
}
