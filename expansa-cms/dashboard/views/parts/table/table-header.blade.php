<?php

use Expansa\Builders\Form;
use Expansa\Builders\Table;
use Expansa\Extensions\Plugin;
use Expansa\Extensions\Theme;
use Expansa\Facades\Extensions;
use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Header of a table: the bar with the title and a badge, the tools, the bulk actions shown while rows are selected
 * and the pagination of `table` on the right, then the filter. The logic is in the `table` component of youla-expansa.js:
 * the markup binds to it by `u-bind`, the values the handlers need are in `data-*`. Rendered by parts/table/table and, with `filter`
 * off, by the API that applies the filter: the bar follows the part of the list, e.g. the trash, while the filter
 * stays as it is, with the search being typed and the popover open. Screens without a table show only the bar.
 *
 * Bar: `create` is the label of the button leading to the `link` URL, e.g. of the page of a new item;
 * `empty` is the button emptying the trash: its `endpoint` and the `data` posted to it. The bulk actions of Table::bulk() are posted
 * to its `endpoint` with the IDs of the checked rows and the `data` fields; the `confirm` actions ask first,
 * the `password` actions show the field of the current password.
 *
 * Pagination, as in WordPress: the range of the shown rows, the first and the previous page, the field of the current
 * page number out of all, the next and the last page; a link or the field opens the page by the `table` component
 * without a reload. Nothing is shown on a single page. `query` is the query the links keep, `$_GET` by default.
 *
 * Filter: the search with the `find` placeholder, applied with the filters 500ms after the typing as the `s` parameter
 * and shown as a chip; the select of the `parts` of the list, see Table::headData(), `part` the current one;
 * the button with the number of active filters of Table::filters() and the popover with rows "field is/is not values".
 * The state and the instant apply are in the `table` component; the popover is anchored to the button by CSS,
 * its selects open inside it, so a click on them does not close it.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/table-header.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$title, $badge, $uploader, $translation, $project, $create, $link, $empty, $parts, $part, $find, $filter] = Safe::data($__data ?? [], [
    'title'       => 'trim',
    'badge'       => 'trim',
    'uploader'    => 'bool:false',
    'translation' => 'bool:false',
    'project'     => 'trim',
    'create'      => 'trim',
    'link'        => 'trim',
    'empty'       => 'array',
    'parts'       => 'array',
    'part'        => 'trim',
    'find'        => 'trim',
    'filter'      => 'bool:true',
])->values();

$table   = ($__data['table'] ?? null) instanceof Table ? $__data['table'] : null;
$filters = $table?->filters() ?? [];
$filter  = $filter && ($filters || $parts || $find);

[$endpoint, $actions, $confirm, $password, $data] = Safe::data($table?->bulk() ?? [], [
    'endpoint' => 'trim',
    'actions'  => 'array',
    'confirm'  => 'array',
    'password' => 'array',
    'data'     => 'array',
])->values();

$progress = static fn (string $name) => [
    'content'     => $name,
    'description' => t(':percent% complete', 0),
];

$pages   = $table?->pages() ?? 1;
$page    = $table?->page() ?? 1;
$perPage = $table?->perPage() ?? 0;
$total   = $table?->total() ?? 0;
$query   = is_array($__data['query'] ?? null) ? $__data['query'] : $_GET;
// the separators of the filters stay readable, see Table::activeFilters()
$href    = static fn (int $number) => '?' . str_replace(['%2C', '%21'], [',', '!'], http_build_query([...$query, 'page' => $number]));
$before  = [
    [
        'page'  => 1,
        'icon'  => 'ph ph-caret-double-left',
        'title' => t_attr('First Page'),
    ],
    [
        'page'  => $page - 1,
        'icon'  => 'ph ph-caret-left',
        'title' => t_attr('Previous Page'),
    ],
];
$after   = [
    [
        'page'  => $page + 1,
        'icon'  => 'ph ph-caret-right',
        'title' => t_attr('Next Page'),
    ],
    [
        'page'  => $pages,
        'icon'  => 'ph ph-caret-double-right',
        'title' => t_attr('Last Page'),
    ],
];

// the selects of the filter rows are written here, not by Form::view(): their script is connected by hand
if ($filter && $filters) {
    Form::assets('select', 'select');
}

$popover = 'table-filter-' . bin2hex(random_bytes(4));
?>
<div class="table-header">
    @if ($title)
        <h5>
            {!! $title !!}
            @if ($badge)
                <span class="badge">{!! $badge !!}</span>
            @endif
        </h5>
    @endif
    @if ($uploader || ! empty($empty['endpoint']) || ($create && $link) || $translation)
        <div class="table-header-group" u-bind="tools">
            @if ($uploader)
                <button class="btn btn--outline btn--xs" type="button" u-bind="uploader">
                    <i class="ph ph-upload-simple"></i> {{ t('Add New File') }}
                </button>
            @endif

            @if (! empty($empty['endpoint']))
                <button class="btn btn--outline btn--xs t-red" type="button" u-bind="emptyTrash" data-endpoint="{{ $empty['endpoint'] }}" data-payload="{{ json_encode((object) ($empty['data'] ?? [])) }}">
                    <i class="ph ph-trash"></i> {{ t('Empty Trash') }}
                </button>
            @endif

            @if ($create && $link)
                <a class="btn btn--secondary btn--xs" href="{{ $link }}">
                    <i class="ph ph-plus"></i> {{ $create }}
                </a>
            @endif

            @if ($translation)
                <form class="table-header-translation" method="GET">
                    <input type="hidden" name="table" value="translation">
                    <div class="table-header-group">
                        <svg width="16" height="16">
                            <use xlink:href="{{ url('/dashboard/assets/sprites/flags.svg#us') }}"></use>
                        </svg>
                        English
                    </div>
                    <span class="badge badge--round badge--icon badge--lg"><i class="ph ph-arrows-left-right"></i></span>
                    <?php
                    echo Form::view('select', [
                        'type'       => 'select',
                        'name'       => 'language',
                        'class'      => 'field field--raised',
                        'attributes' => ['u-select' => '', 'name' => 'language'],
                        'options'    => I18n::languageOptions(),
                    ]);

                    echo Form::view('select', [
                        'type'       => 'select',
                        'name'       => 'project',
                        'class'      => 'field field--raised',
                        'attributes' => ['u-select' => '', 'name' => 'project', 'value' => $project],
                        // an empty group would render as an option
                        'options'    => array_filter([
                            'core'    => [
                                'label'   => t('Core'),
                                'options' => ['core' => $progress(t('Expansa Core'))],
                            ],
                            'plugins' => [
                                'label'   => t('Plugins'),
                                'options' => array_map(fn (Plugin $plugin) => $progress($plugin->name), array_column(Extensions::get('plugin'), null, 'id')),
                            ],
                            'themes'  => [
                                'label'   => t('Themes'),
                                'options' => array_map(fn (Theme $theme) => $progress($theme->name), array_column(Extensions::get('theme'), null, 'id')),
                            ],
                        ], fn (array $group) => $group['options'] !== []),
                    ]);
                    ?>
                    <button type="submit" class="btn btn--outline">
                        <i class="ph ph-scan"></i> {{ t('Scan') }}
                    </button>
                </form>
            @endif
        </div>
    @endif

    @if ($actions)
        <div class="table-header-group" u-bind="bulkGroup" hidden>
            <form class="df g-1" method="POST" action="{{ $endpoint }}" u-bind="bulkForm"@if ($confirm) data-confirm="{{ json_encode(array_values($confirm)) }}"@endif@if ($password) data-password="{{ json_encode(array_values($password)) }}"@endif>
                @foreach ($data as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                <?php
                echo Form::view('select', [
                    'type'       => 'select',
                    'name'       => 'action',
                    'error'      => 'action',
                    'class'      => 'field field--xs field--raised',
                    'attributes' => [
                        'u-prop'   => 'action',
                        'u-select' => '',
                    ],
                    'options'    => ['' => t('Bulk Actions'), ...$actions],
                ]);
                ?>
                @if ($password)
                    <div class="field field--xs field--raised" u-bind="bulkPassword" hidden>
                        <div class="field-item">
                            <input type="password" name="password" autocomplete="current-password" placeholder="{{ t_attr('Your current password') }}">
                        </div>
                    </div>
                @endif
                <button class="btn btn--xs btn--secondary" type="submit" u-bind="bulkApply"><i class="ph ph-paper-plane-tilt"></i> {{ t('Apply') }}</button>
            </form>
        </div>
    @endif

    <div class="table-header-pagination">
        @if ($pages > 1)
            <nav class="table-pagination" aria-label="{{ t_attr('Pagination') }}">
                <span class="t-muted">{{ t(':from–:to of :total', ($page - 1) * $perPage + 1, min($page * $perPage, $total), $total) }}</span>
                <div class="table-pagination-pages">
                    @foreach ($before as $step)
                        @if ($page > 1)
                            <a class="btn btn--xs btn--icon" href="{{ $href($step['page']) }}" title="{{ $step['title'] }}" u-bind="pageLink" data-page="{{ $step['page'] }}"><i class="{{ $step['icon'] }}"></i></a>
                        @else
                            <span class="btn btn--xs btn--icon" aria-disabled="true"><i class="{{ $step['icon'] }}"></i></span>
                        @endif
                    @endforeach
                    <span class="field field--xs field--raised table-pagination-field">
                        <span class="field-item">
                            <input type="number" value="{{ $page }}" min="1" max="{{ $pages }}" step="1" aria-label="{{ t_attr('Current Page') }}" u-bind="pageField">
                        </span>
                    </span>
                    <span class="t-muted">{{ t('of :pages', $pages) }}</span>
                    @foreach ($after as $step)
                        @if ($page < $pages)
                            <a class="btn btn--xs btn--icon" href="{{ $href($step['page']) }}" title="{{ $step['title'] }}" u-bind="pageLink" data-page="{{ $step['page'] }}"><i class="{{ $step['icon'] }}"></i></a>
                        @else
                            <span class="btn btn--xs btn--icon" aria-disabled="true"><i class="{{ $step['icon'] }}"></i></span>
                        @endif
                    @endforeach
                </div>
            </nav>
        @endif
    </div>
</div>

@if ($filter)
<div class="table-filter">
    <div class="table-filter-actions">
        <?php
        if ($find) {
            echo Form::view('input', [
                'type'       => 'search',
                'name'       => 's',
                'before'     => '<i class="ph ph-magnifying-glass"></i>',
                'class'      => 'field field--sm field--raised',
                'attributes' => [
                    'type'        => 'search',
                    'name'        => 's',
                    'placeholder' => $find,
                    'u-prop'      => 'search',
                    'u-bind'      => 'searchField',
                ],
            ]);
        }
        if ($parts) {
            echo Form::view('select', [
                'type'       => 'select',
                'name'       => 'part',
                'before'     => '<i class="ph ph-circle-dashed"></i> ' . t('Status:'),
                'class'      => 'field field--sm field--plain',
                'attributes' => [
                    'u-select' => true,
                    'u-bind'   => 'partSelect',
                    'value'    => $part,
                ],
                'options'    => array_map(fn (array $item) => (string) ($item['label'] ?? ''), $parts),
            ]);
        }
        ?>
        @if ($filters)
            <button class="btn btn--sm" type="button" popovertarget="{{ $popover }}">
                <i class="ph ph-funnel"></i> {{ t('Filter') }}
                <span class="badge" u-bind="filterCount" hidden></span>
            </button>
        @endif
    </div>

    @if ($filters)
        <div class="table-filter-content" id="{{ $popover }}" popover>
            <div class="table-filter-head">
                <strong>{{ t('Filters') }}:</strong>
                <div class="table-filter-add">
                    @foreach ($filters as $key => $field)
                        <button class="badge" type="button" u-bind="filterAdd" data-field="{{ $key }}"><i class="{{ $field['icon'] ?: 'ph ph-circle-half-tilt' }}"></i> {{ $field['label'] }}</button>
                    @endforeach
                </div>
                <button class="btn btn--xs t-red" type="button" u-bind="filterClear">{{ t('Clear all') }}</button>
            </div>
            <div class="table-filter-empty" u-bind="filterEmpty">{{ t('No filters applied. Combine filters to narrow the list, e.g. Status is Draft and Author is you.') }}</div>
            <div class="table-filter-rows">
                <div class="table-filter-row" u-each="(row, i) in rows">
                    <span class="table-filter-conjunction" u-bind="filterConjunction"></span>
                    <select class="field field--xs" u-bind="filterField">
                        @foreach ($filters as $key => $field)
                            <option value="{{ $key }}">{{ $field['label'] }}</option>
                        @endforeach
                    </select>
                    <select class="field field--xs" u-bind="filterOperator">
                        <option value="is">{{ t('is') }}</option>
                        <option value="not">{{ t('is not') }}</option>
                    </select>
                    <select class="field field--xs" multiple u-bind="filterValues"></select>
                    <button class="btn btn--xs btn--icon t-red" type="button" title="{{ t('Remove filter') }}" u-bind="filterRemove"><i class="ph ph-trash-simple"></i></button>
                </div>
            </div>
        </div>
    @endif

    @if ($filters || $find)
        <div class="table-filter-chips" u-bind="chips" hidden>
            <i class="ph ph-funnel"></i>
            <span class="table-filter-chip-list">
                @if ($find)
                    <span class="table-filter-chip-wrap" u-bind="searchChip">
                        <span class="table-filter-chip">
                            <button class="table-filter-chip-edit" type="button" u-bind="searchChipEdit">
                                <i class="ph ph-magnifying-glass"></i>
                                <strong>{{ t('Search') }}</strong>
                                <span u-bind="searchChipText"></span>
                            </button>
                            <button class="table-filter-chip-remove" type="button" title="{{ t('Clear search') }}" u-bind="searchChipRemove">
                                <i class="ph ph-x"></i>
                            </button>
                        </span>
                    </span>
                @endif
                <span class="table-filter-chip-wrap" u-each="(row, i) in rows" u-bind="filterChip">
                    <span u-bind="filterChipAnd">{{ t('and') }}</span>
                    <span class="table-filter-chip">
                        <button class="table-filter-chip-edit" type="button" popovertarget="{{ $popover }}-values" u-bind="filterChipEdit">
                            <i u-bind="filterChipIcon"></i>
                            <strong u-bind="filterChipLabel"></strong>
                            <span u-bind="filterChipOperator"></span>
                            <span u-bind="filterChipValues"></span>
                        </button>
                        <button class="table-filter-chip-remove" type="button" title="{{ t('Remove filter') }}" u-bind="filterChipRemove">
                            <i class="ph ph-x"></i>
                        </button>
                    </span>
                </span>
            </span>
            @if ($filters)
                <button class="btn btn--xs" type="button" popovertarget="{{ $popover }}"><i class="ph ph-plus"></i> {{ t('Add') }}</button>
            @endif
            <button class="btn btn--xs t-red" type="button" u-bind="clearAll">{{ t('Clear all') }}</button>
            @if ($filters)
                <div class="table-filter-values" id="{{ $popover }}-values" popover u-bind="valuesPopover"></div>
            @endif
        </div>
    @endif
</div>
@endif
