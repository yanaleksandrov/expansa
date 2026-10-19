<?php

/**
 * Pagination in the header of a table, as in WordPress: the range of the shown rows, the first and the previous
 * page, the field of the current page number out of all, the next and the last page; the links unavailable
 * on the current page are disabled. A link or the field opens the page by the `table` component without a reload.
 * Nothing is shown on a single page. Rendered by the table and by the API that changes the page.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/pagination.blade.php
 *
 * @var Expansa\Builders\Table $table
 * @var array                  $query Query of the page the links keep, `$_GET` by default.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$pages   = $table->pages();
$page    = $table->page();
$perPage = $table->perPage();
$total   = $table->total();
$query   = $query ?? $_GET;
// the separators of the filters stay readable, see Table::activeFilters()
$href    = static fn (int $number) => '?' . str_replace(['%2C', '%21'], [',', '!'], http_build_query([...$query, 'page' => $number]));

$before = [
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
$after  = [
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
?>
@if ($pages > 1)
<nav class="table-pagination" aria-label="{{ t_attr('Pagination') }}">
    <span class="t-muted">{{ t(':from–:to of :total', ($page - 1) * $perPage + 1, min($page * $perPage, $total), $total) }}</span>
    <div class="table-pagination-pages">
        @foreach ($before as $link)
            @if ($page > 1)
                <a class="btn btn--xs btn--icon" href="{{ $href($link['page']) }}" title="{{ $link['title'] }}" @click.prevent="goToPage({{ $link['page'] }})"><i class="{{ $link['icon'] }}"></i></a>
            @else
                <span class="btn btn--xs btn--icon" aria-disabled="true"><i class="{{ $link['icon'] }}"></i></span>
            @endif
        @endforeach
        <span class="field field--xs field--raised table-pagination-field">
            <span class="field-item">
                <input type="number" value="{{ $page }}" min="1" max="{{ $pages }}" step="1" aria-label="{{ t_attr('Current Page') }}" @change="$el.value && goToPage(Math.min({{ $pages }}, Math.max(1, Math.trunc($el.value))))">
            </span>
        </span>
        <span class="t-muted">{{ t('of :pages', $pages) }}</span>
        @foreach ($after as $link)
            @if ($page < $pages)
                <a class="btn btn--xs btn--icon" href="{{ $href($link['page']) }}" title="{{ $link['title'] }}" @click.prevent="goToPage({{ $link['page'] }})"><i class="{{ $link['icon'] }}"></i></a>
            @else
                <span class="btn btn--xs btn--icon" aria-disabled="true"><i class="{{ $link['icon'] }}"></i></span>
            @endif
        @endforeach
    </div>
</nav>
@endif
