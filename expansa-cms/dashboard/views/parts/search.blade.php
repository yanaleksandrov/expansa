<?php
/**
 * Search of the dashboard in the top bar, a popover opened by the button or Ctrl+K: the browser closes it
 * on Esc and on a click outside and focuses the input. The `search` component of youla-expansa.js gets
 * the results from App\Api\Search: links and section headers, the ones without a URL; every binding
 * of the markup is its `u-bind` set.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/search.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="search" u-data="search">
    <button class="search-btn" type="button" popovertarget="expansa-search">
        <i class="ph ph-magnifying-glass"></i> {!! t('Search') !!} <code>Ctrl+K</code>
    </button>
    <div class="search-box" id="expansa-search" popover>
        <input class="search-input" type="search" name="search" placeholder="{{ t_attr('Search') }}" autocomplete="off" autofocus u-bind="input">
        <ul class="search-results" u-bind="results" hidden>
            <li class="search-item" u-bind="row">
                <a class="search-link" u-bind="anchor">
                    <span class="search-text" u-bind="label"></span>
                    <span class="t-muted">{!! t('Jump to') !!}</span>
                </a>
                <span class="search-header" u-bind="heading"></span>
            </li>
        </ul>
        <div class="search-results" u-bind="idle">
            <?php
            echo view('parts/state', [
                'icon'        => 'ufo',
                'title'       => t('Search'),
                'description' => t('Start typing to see search results.'),
            ]);
            ?>
        </div>
        <div class="search-results" u-bind="empty" hidden>
            <?php
            echo view('parts/state', [
                'icon'        => 'ufo',
                'title'       => t('Nothing found'),
                'description' => t('Try another word.'),
            ]);
            ?>
        </div>
        <div class="search-help">
            <div class="df aic g-1"><i class="ph ph-arrow-up"></i><i class="ph ph-arrow-down"></i> {!! t('Move') !!}</div>
            <div class="df aic g-1"><code>Esc</code> {!! t('Close') !!}</div>
            <div class="df aic g-1"><i class="ph ph-arrow-elbow-down-left"></i> {!! t('Select') !!}</div>
        </div>
    </div>
</div>
