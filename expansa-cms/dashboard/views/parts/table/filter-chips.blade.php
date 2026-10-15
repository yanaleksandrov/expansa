<?php
/**
 * Active filters of a table: a chip per filter with values and the button that removes it, "Add" that opens
 * the filter popover under itself and "Clear all"; rendered from the state of the `table` component.
 * A click on a chip opens the popover with the values of its filter under it.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/filter-chips.blade.php
 *
 * @var string $popover ID of the filter popover.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="table-filter-chips" u-show="activeFilters().length" hidden>
    <i class="ph ph-funnel"></i>
    <span class="table-filter-chip-list">
        <span class="table-filter-chip-wrap" u-each="(row, i) in rows" u-show="row.values.length">
            <span u-show="!isFirstActive(i)">{{ t('and') }}</span>
            <span class="table-filter-chip">
                <button class="table-filter-chip-edit" type="button" popovertarget="{{ $popover }}-values" @click="editing = i">
                    <i :class="fields[row.field]?.icon"></i>
                    <strong u-text="fields[row.field]?.label"></strong>
                    <span u-text="row.operator === 'not' ? {{ json_encode(t('is not')) }} : {{ json_encode(t('is')) }}"></span>
                    <span u-text="filterLabels(row, {{ json_encode(t(':count selected')) }})"></span>
                </button>
                <button class="table-filter-chip-remove" type="button" title="{{ t('Remove filter') }}" @click="removeFilter(i)"><i class="ph ph-x"></i></button>
            </span>
        </span>
    </span>
    <button class="btn btn--xs" type="button" popovertarget="{{ $popover }}"><i class="ph ph-plus"></i> {{ t('Add') }}</button>
    <button class="btn btn--xs t-red ml-auto" type="button" @click="clearFilters()">{{ t('Clear all') }}</button>
    <div class="table-filter-values" id="{{ $popover }}-values" popover u-html="filterChoices(editing)" @change="toggleFilterValue($event.target)"></div>
</div>
