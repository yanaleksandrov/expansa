<?php

declare(strict_types=1);

namespace Dashboard\Table\Traits;

trait Table
{
    /**
     * Tag for table wrapper.
     *
     * @var string
     */
    public string $tag = 'div';

    /**
     * Table attributes list.
     *
     * @var array
     */
    public array $attributes = [];

    /**
     * Data for render table.
     *
     * @since 2025.1
     * @var array
     */
    public array $data = [];

    /**
     * Content after the output of the data.
     *
     * @since 2025.1
     * @var string
     */
    public string $dataAfter = '';

    /**
     * Content before the output of the data.
     *
     * @since 2025.1
     * @var string
     */
    public string $dataBefore = '';

    /**
     * Variable for loop.
     *
     * @since 2025.1
     * @var string
     */
    public string $dataVariable = 'items';

    /**
     * Template to output form data.
     *
     * @since 2025.1
     * @var string
     */
    public string $dataTemplate = 'table/data';

    /**
     * Rows settings.
     *
     * @since 2025.1
     * @var array
     */
    public array $rows = [];

    /**
     * Columns list.
     *
     * @since 2025.1
     * @var array
     */
    public array $columns = [];

    /**
     * Data for render table header.
     *
     * @since 2025.1
     * @var array
     */
    public array $headerContent = [];

    /**
     * Template to get table header.
     *
     * @since 2025.1
     * @var string
     */
    public string $headerTemplate = 'table/header';

    /**
     * Content after not found data.
     *
     * @since 2025.1
     * @var string
     */
    public string $notFoundAfter = '';

    /**
     * Content before not found data.
     *
     * @since 2025.1
     * @var string
     */
    public string $notFoundBefore = '';

    /**
     * Data for not found template partial.
     *
     * @since 2025.1
     * @var array
     */
    public array $notFoundContent = [];

    /**
     * Template to get for not found.
     *
     * @since 2025.1
     * @var string
     */
    public string $notFoundTemplate = 'global/state';

    /**
     * Use table header or not.
     *
     * @since 2025.1
     * @var string
     */
    public string $cellHeadTemplate = 'cell-head';

    /**
     * The path to the folder with the template files.
     *
     * @since 2025.1
     * @var string
     */
    public string $views = 'table';
}
