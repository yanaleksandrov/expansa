<?php

declare(strict_types=1);

namespace Expansa\Builders\Table;

/**
 * Column of a dashboard table, described with fluent setters:
 * `$this->cell('date')->title(t('Date'))->fixedWidth('9rem')->view('date')`.
 *
 * The view of a cell gets the column as `$cell`, the row as `$item` and its value by the key as `$value`.
 *
 * @package Expansa\Builders\Table
 */
final class Cell
{
    /**
     * Prefix of the cell views, a kind is added to it: `parts/table/cell` + `date`.
     */
    private static string $views = '';

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
         * View that renders the cells of the column, the `raw` kind by default.
         */
        public string $view = '' {
            get => $this->view !== '' ? $this->view : self::$views . '-raw';
        },

        /**
         * Whether the column can be sorted.
         */
        public bool $sortable = false,

        /**
         * Key of the row value the column is sorted by, e.g. a raw date of a formatted one; the column key by default.
         */
        public string $sortBy = '' {
            get => $this->sortBy !== '' ? $this->sortBy : $this->key;
        },

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
         *
         * @var array<string, mixed>
         */
        public array $attributes = [],
    ) {}

    /**
     * Set the prefix of the cell views.
     *
     * @param string $views View name, e.g. `parts/table/cell`.
     * @return void
     */
    public static function configure(string $views): void
    {
        self::$views = $views;
    }

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
     * @param array<string, mixed> $attributes
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
     * @param string $by Key of the row value it is sorted by, the column key by default.
     * @return static
     */
    public function sortable(string $by = ''): static
    {
        $this->sortable = true;
        $this->sortBy   = $by;

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
     * Set the view of the cells: a view name with `/` or a file path as is, a kind otherwise
     * (`date` → `parts/table/cell-date`).
     *
     * @param string $view
     * @return static
     */
    public function view(string $view): static
    {
        $this->view = str_contains($view, '/') || file_exists($view) ? $view : self::$views . "-$view";

        return $this;
    }
}
