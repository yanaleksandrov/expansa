<?php

use Expansa\Facades\Safe;

/**
 * Header above a table: the bar of parts/table/header-bar with the title, the tools, the bulk actions
 * and the pagination, then the filter of parts/table/filter and the `content`. `parts` are the parts
 * of the list for the select of the filter, see parts/table/filter, and `part` is the current one;
 * `find` is the placeholder of the search of the filter.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/header.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$content, $filters, $parts, $part, $find] = Safe::data($__data ?? [], [
    'content' => 'trim',
    'filters' => 'array',
    'parts'   => 'array',
    'part'    => 'trim',
    'find'    => 'trim',
])->values();

echo view('parts/table/header-bar', $__data ?? []);
?>

@if ($filters || $parts || $find)
    <?php echo view('parts/table/filter', [
        'filters' => $filters,
        'parts'   => $parts,
        'part'    => $part,
        'find'    => $find,
    ]); ?>
@endif

{!! $content !!}
