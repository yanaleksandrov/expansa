<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Table row content template can be overridden by copying it to themes/yourtheme/dashboard/views/table/row.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

if (!is_array($data) || empty($row) || empty($columns)) {
    return;
}

[$tag, $view, $attributes] = Safe::data(
    (array)$row,
    [
        'tag'        => 'tag',
        'view'       => 'trim',
        'attributes' => 'array',
    ]
)->values();

$tag && printf( '<%s>', trim( sprintf( '%s %s', $tag, Arr::toHtmlAtts( $attributes ) ) ) );
foreach ( $columns as $column ) {
	echo view( $column->view, [ ...(array) $column, ...$data ] );
}
$tag && printf( '</%s>' . PHP_EOL, $tag );