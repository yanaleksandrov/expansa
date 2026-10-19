<?php
use Expansa\Builders\Form;

/**
 * Filter of a table: the search, the select of the parts of the list, the button with the number of active filters
 * and the popover with rows "field is/is not values"; the state and the instant apply are in the `table` component.
 * The popover is anchored to the button by CSS, its selects open inside it, so a click on them does not close it.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/filter.blade.php
 *
 * @var array<string, array{label: string, icon?: string, options: array}> $filters Fields of Table::filters().
 * @var array<string, string> $statuses Labels of the parts of the list by status, `trash` for the trash;
 *                                      the select opens the chosen one.
 * @var string                $status   Current part.
 * @var string                $find     Placeholder of the search, applied with the filters 500ms after the typing
 *                                      as the `s` parameter and shown as a chip; no search when empty.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

// the selects of the rows are written here, not by Form::view(): their script is connected by hand
if ($filters) {
    Form::assets('select', 'select');
}

// rows are initialized before they are attached, so the popover is found from the component root
$select  ="{ contentLocation: \$root.querySelector('.table-filter-content') }";
$popover = 'table-filter-' . bin2hex(random_bytes(4));
$change  = "const q = new URLSearchParams(location.search); q.delete('status'); q.delete('view'); q.delete('page');"
    . " \$el.value === 'trash' ? q.set('view', 'trash') : \$el.value && q.set('status', \$el.value); location.search = query(q)";
?>
<div class="table-filter">
    <div class="table-filter-actions df g-1">
        <?php
        if ($find) {
            echo Form::view('input', [
                'type'       => 'search',
                'name'       => 's',
                'before'     => '<i class="ph ph-magnifying-glass"></i>',
                'class'      => 'field field--sm field--raised',
                'attributes' => [
                    'type'                   => 'search',
                    'name'                   => 's',
                    'placeholder'            => $find,
                    'u-prop'                 => 'search',
                    '@input.delay.500ms'     => 'applyFilters()',
                    '@keydown.enter.prevent' => 'applyFilters()',
                ],
            ]);
        }
        if ($statuses) {
            echo Form::view('select', [
                'type'       => 'select',
                'name'       => 'status',
                'before'     => '<i class="ph ph-circle-dashed"></i>' . t('Status:'),
                'class'      => 'field field--sm field--plain',
                'attributes' => [
                    'u-select' => true,
                    'value'    => $status,
                    '@change'  => $change,
                ],
                'options'    => $statuses,
            ]);
        }
        ?>
        @if ($filters)
            <button class="btn btn--sm" type="button" popovertarget="{{ $popover }}">
                <i class="ph ph-funnel"></i> {{ t('Filter') }}
                <span class="badge" u-show="activeFilters().length" u-text="activeFilters().length" hidden></span>
            </button>
        @endif
    </div>

    @if ($filters)
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
                        <i class="{{ $field['icon'] ?: 'ph ph-circle-half-tilt' }}"></i> {{ $field['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($filters || $find)
        <div class="table-filter-chips" u-show="activeFilters().length || search.trim()" hidden>
            <i class="ph ph-funnel"></i>
            <span class="table-filter-chip-list">
                @if ($find)
                    <span class="table-filter-chip-wrap" u-show="search.trim()">
                        <span class="table-filter-chip">
                            <button class="table-filter-chip-edit" type="button" @click="$root.querySelector('.table-filter [name=s]')?.focus()">
                                <i class="ph ph-magnifying-glass"></i>
                                <strong>{{ t('Search') }}</strong>
                                <span u-text="search.trim()"></span>
                            </button>
                            <button class="table-filter-chip-remove" type="button" title="{{ t('Clear search') }}" @click="clearSearch()">
                                <i class="ph ph-x"></i>
                            </button>
                        </span>
                    </span>
                @endif
                <span class="table-filter-chip-wrap" u-each="(row, i) in rows" u-show="row.values.length">
                    <span u-show="search.trim() || !isFirstActive(i)">{{ t('and') }}</span>
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
            @if ($filters)
                <button class="btn btn--xs" type="button" popovertarget="{{ $popover }}"><i class="ph ph-plus"></i> {{ t('Add') }}</button>
            @endif
            <button class="btn btn--xs t-red" type="button" @click="clearFilters()">{{ t('Clear all') }}</button>
            @if ($filters)
                <div class="table-filter-values" id="{{ $popover }}-values" popover u-html="filterChoices(editing)" @change="toggleFilterValue($event.target)"></div>
            @endif
        </div>
    @endif
</div>