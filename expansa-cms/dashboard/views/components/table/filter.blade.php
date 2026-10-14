<?php
/**
 * Filter of a table: the button with the number of active filters and the popover with rows
 * "field is/is not values"; the state and the instant apply are in the `table` component.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/filter.blade.php
 *
 * @var array<string, array{label: string, icon?: string, options: array}> $filters Fields of Table::filters().
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<details class="details table-filter" @click.outside="$el.removeAttribute('open')">
    <summary class="btn btn--sm">
        <i class="ph ph-funnel"></i> {{ t('Filter') }}
        <span class="badge" u-show="activeFilters().length" u-text="activeFilters().length" hidden></span>
    </summary>
    <div class="details-content table-filter-content">
        <div class="table-filter-head">
            <strong>{{ t('Filters') }}</strong>
            <button class="btn btn--xs t-red" type="button" u-show="rows.length" @click="clearFilters()">{{ t('Clear all') }}</button>
        </div>
        <div class="table-filter-empty" u-show="!rows.length">{{ t('No filters applied. Combine filters to narrow the list, e.g. Status is Draft and Author is you.') }}</div>
        <div class="table-filter-rows">
            <div class="table-filter-row" u-each="(row, i) in rows">
                <span class="table-filter-conjunction" u-text="i ? {{ json_encode(t('and')) }} : {{ json_encode(t('Where')) }}"></span>
                <select class="field" :value="row.field" @change="setFilterField(i, $el.value)" u-select>
                    @foreach ($filters as $key => $field)
                        <option value="{{ $key }}">{{ $field['label'] }}</option>
                    @endforeach
                </select>
                <select class="field" :value="row.operator" @change="setFilterOperator(i, $el.value)" u-select>
                    <option value="is">{{ t('is') }}</option>
                    <option value="not">{{ t('is not') }}</option>
                </select>
                <select class="field" multiple u-html="filterOptions(row)" :value="row.values" @change="setFilterValues(i, $el)" u-select></select>
                <button class="btn btn--icon t-red" type="button" title="{{ t('Remove filter') }}" @click="removeFilter(i)"><i class="ph ph-trash-simple"></i></button>
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
</details>
