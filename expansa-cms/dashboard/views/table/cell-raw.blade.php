<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cells/raw.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

[$prop, $attributes, $source, $value] = Safe::data(
    $__data ?? [],
    [
        'key'        => 'prop',
        'attributes' => 'array',
        'source'     => 'trim',
        'value'      => 'trim',
    ]
)->values();
?>
<div<?php echo Arr::toHtmlAtts($attributes); ?> x-text="item.{{ $prop }}">{{ $source }}</div>
