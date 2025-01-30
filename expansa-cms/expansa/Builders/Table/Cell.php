<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

use Expansa\Facades\Safe;

final class Cell
{
    use \Expansa\Builders\Table\Traits\Cell;

    /**
     * Add new column.
     *
     * @param string $key
     * @return Cell
     */
    public static function add(string $key): Cell
    {
        return new self($key);
    }

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
     * Default sort ordering.
     *
     * @param string $order
     * @return Cell
     */
    public function sortOrder(string $order = 'DESC'): Cell
    {
        $order = Safe::uppercase($order);
        if (in_array($order, [ 'ASC', 'DESC' ], true)) {
            $this->sortOrder = $order;
        }
        $this->sortOrder = 'DESC';

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
     * @param string $template
     * @return Cell
     */
    public function view(string $template): Cell
    {
        if (file_exists($template)) {
            $this->view = $template;
        } else {
            $this->view = sprintf('%s-%s', $this->view, $template);
        }

        return $this;
    }
}
