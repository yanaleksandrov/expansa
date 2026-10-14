<?php
/**
 * Active filters of a table: a chip per filter with values and the button that removes it, and "Clear all";
 * rendered from the state of the `table` component.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/filter-chips.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="table-filter-chips" u-show="activeFilters().length" hidden>
    <i class="ph ph-funnel"></i>
    <span class="table-filter-chip-wrap" u-each="(row, i) in rows" u-show="row.values.length">
        <span u-show="!isFirstActive(i)">{{ t('and') }}</span>
        <span class="table-filter-chip">
            <i :class="fields[row.field]?.icon"></i>
            <strong u-text="fields[row.field]?.label"></strong>
            <span u-text="row.operator === 'not' ? {{ json_encode(t('is not')) }} : {{ json_encode(t('is')) }}"></span>
            <span u-text="filterLabels(row, {{ json_encode(t(':count selected')) }})"></span>
            <button type="button" title="{{ t('Remove filter') }}" @click="removeFilter(i)"><i class="ph ph-x"></i></button>
        </span>
    </span>
    <button class="btn btn--xs t-red ml-auto" type="button" @click="clearFilters()">{{ t('Clear all') }}</button>
</div>
