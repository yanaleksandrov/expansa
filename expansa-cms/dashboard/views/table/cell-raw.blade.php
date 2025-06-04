<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Table raw text cell
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/cell-raw.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

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
{{ $__data[$prop] ?? '' }}
