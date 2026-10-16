<?php
use Expansa\Builders\Form;

/**
 * Filter of a table: the button with the number of active filters and the popover with rows
 * "field is/is not values"; the state and the instant apply are in the `table` component.
 * The popover is anchored to the button by CSS, its selects open inside it, so a click on them does not close it.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/filter.blade.php
 *
 * @var array<string, array{label: string, icon?: string, options: array}> $filters Fields of Table::filters().
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// rows are initialized before they are attached, so the popover is found from the component root
$select  = "{ contentLocation: \$root.querySelector('.table-filter-content') }";
$popover = 'table-filter-' . bin2hex(random_bytes(4));
?>
<div class="table-filter">
    <div class="table-filter-actions df fww g-1">
        <div>
            <?php
            echo Form::view('input', [
                'type'       => 'search',
                'name'       => 's',
                'before'     => '<i class="ph ph-magnifying-glass"></i>',
                'class'      => 'field field--sm field--raised',
                'attributes' => ['type' => 'search', 'name' => 's', 'placeholder' => t('Search...')],
            ]);
            ?>
        </div>
        <button class="btn btn--sm" type="button" popovertarget="{{ $popover }}">
            <i class="ph ph-funnel"></i> {{ t('Filter') }}
            <span class="badge" u-show="activeFilters().length" u-text="activeFilters().length" hidden></span>
        </button>
    </div>

    <div class="table-filter-content" id="{{ $popover }}" popover>
        <div class="table-filter-head">
            <strong>{{ t('Filters') }}</strong>
            <button class="btn btn--xs t-red" type="button" u-show="rows.length" @click="clearFilters()">{{ t('Clear all') }}</button>
        </div>
        <div class="table-filter-empty" u-show="!rows.length">{{ t('No filters applied. Combine filters to narrow the list, e.g. Status is Draft and Author is you.') }}</div>
        <div class="table-filter-rows">
            <div class="table-filter-row" u-each="(row, i) in rows">
                <span class="table-filter-conjunction" u-text="i ? texts.and : texts.where"></span>
                <select class="field field--xs" :value="row.field" @change="setFilterField(i, $el.value)" u-select="{{ $select }}">
                    @foreach ($filters as $key => $field)
                        <option value="{{ $key }}">{{ $field['label'] }}</option>
                    @endforeach
                </select>
                <select class="field field--xs" :value="row.operator" @change="setFilterOperator(i, $el.value)" u-select="{{ $select }}">
                    <option value="is">{{ t('is') }}</option>
                    <option value="not">{{ t('is not') }}</option>
                </select>
                <select class="field field--xs" multiple u-html="filterOptions(row)" :value="row.values" @change="setFilterValues(i, $el)" u-select="{{ $select }}"></select>
                <button class="btn btn--xs btn--icon t-red" type="button" title="{{ t('Remove filter') }}" @click="removeFilter(i)"><i class="ph ph-trash-simple"></i></button>
            </div>
        </div>
        <div class="table-filter-add">
            <span class="t-muted">{{ t('Add filter:') }}</span>
            @foreach ($filters as $key => $field)
                <button class="badge" type="button" @click="addFilter({{ json_encode($key) }})">
                    @if (! empty($field['icon']))
                        <i class="{{ $field['icon'] }}"></i>
                    @endif
                    {{ $field['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="table-filter-chips" u-show="activeFilters().length" hidden>
        <i class="ph ph-funnel"></i>
        <span class="table-filter-chip-list">
            <span class="table-filter-chip-wrap" u-each="(row, i) in rows" u-show="row.values.length">
                <span u-show="!isFirstActive(i)">{{ t('and') }}</span>
                <span class="table-filter-chip">
                    <button class="table-filter-chip-edit" type="button" popovertarget="{{ $popover }}-values" @click="editing = i">
                        <i :class="fields[row.field]?.icon"></i>
                        <strong u-text="fields[row.field]?.label"></strong>
                        <span u-text="row.operator === 'not' ? texts.isNot : texts.is"></span>
                        <span u-text="filterLabels(row, texts.selected)"></span>
                    </button>
                    <button class="table-filter-chip-remove" type="button" title="{{ t('Remove filter') }}" @click="removeFilter(i)">
                        <i class="ph ph-x"></i>
                    </button>
                </span>
            </span>
        </span>
        <button class="btn btn--xs" type="button" popovertarget="{{ $popover }}"><i class="ph ph-plus"></i> {{ t('Add') }}</button>
        <button class="btn btn--xs t-red" type="button" @click="clearFilters()">{{ t('Clear all') }}</button>
        <div class="table-filter-values" id="{{ $popover }}-values" popover u-html="filterChoices(editing)" @change="toggleFilterValue($event.target)"></div>
    </div>
</div>