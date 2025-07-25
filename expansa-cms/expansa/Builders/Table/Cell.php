<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

class Cell
{
    /**
     * Column constructor.
     *
     * @param string $key Unique column key.
     * @param string $title Title of column.
     * @param string $view Path to get view for render column cell.
     * @param bool $sortable Column is sortable.
     * @param string $width Min column width.
     * @param bool $flexible Column width is flexible.
     * @param bool $searchable Column is searchable.
     * @param array $attributes Cell wrapper HTML attributes list.
     */
    public function __construct(
        public string $key = '',
        public string $title = '',
        public string $view = 'table/cell',
        public bool $sortable = false,
        public string $width = '',
        public bool $flexible = false,
        public bool $searchable = false,
        public array $attributes = []
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
