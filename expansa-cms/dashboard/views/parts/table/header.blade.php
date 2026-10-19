<?php

use Expansa\Builders\Form;
use Expansa\Extensions\Plugin;
use Expansa\Extensions\Theme;
use Expansa\Facades\Extensions;
use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Header above a table: the title with a badge, the tools, the bulk actions shown while rows are selected
 * and the `pagination` on the right, HTML of parts/table/pagination.
 * `create` is the label of the button leading to the `link` URL, e.g. of the page of a new item;
 * `bulk` are the bulk actions of Table::bulk(); `statuses` are labels of the parts of the list
 * for the filter by status, `trash` for the trash, and `status` is the current one; `find` is the placeholder
 * of the search of the filter; `empty` is the click expression of the button emptying the trash.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/header.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$title, $badge, $content, $pagination, $uploader, $filters, $bulk, $search, $translation, $project, $create, $link, $statuses, $status, $find, $empty] = Safe::data($__data ?? [], [
    'title'       => 'trim',
    'badge'       => 'trim',
    'content'     => 'trim',
    'pagination'  => 'trim',
    'uploader'    => 'bool:false',
    'filters'     => 'array',
    'bulk'        => 'array',
    'search'      => 'trim',
    'translation' => 'bool:false',
    'project'     => 'trim',
    'create'      => 'trim',
    'link'        => 'trim',
    'statuses'    => 'array',
    'status'      => 'trim',
    'find'        => 'trim',
    'empty'       => 'trim',
])->values();

$progress = static fn (string $name) => [
    'content'     => $name,
    'description' => t(':percent% complete', 0),
];
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

    <div class="table-header-group" u-show="!bulk">
        @if ($uploader)
            <button class="btn btn--outline btn--xs" type="button" @click="$dialog.open('tmpl-media-uploader', uploaderDialog)">
                <i class="ph ph-upload-simple"></i> {{ t('Add New File') }}
            </button>
        @endif

        @if ($empty)
            <button class="btn btn--outline btn--xs t-red" type="button" @click="{{ $empty }}">
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

    @if (! empty($bulk['actions']))
        <div class="table-header-group" u-show="bulk" hidden>
            <?php echo view('parts/table/bulk', $bulk); ?>
        </div>
    @endif

    <div class="table-header-pagination">{!! $pagination !!}</div>
</div>

@if ($filters || $statuses || $find)
    <?php echo view('parts/table/filter', [
        'filters'  => $filters,
        'statuses' => $statuses,
        'status'   => $status,
        'find'     => $find,
    ]); ?>
@endif

{!! $content !!}
