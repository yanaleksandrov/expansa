<?php

use Expansa\Builders\Form;
use Expansa\Extensions\Plugin;
use Expansa\Extensions\Theme;
use Expansa\Facades\Extensions;
use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Header above a table: the title with a badge, the tools and the bulk actions shown while rows are selected.
 * `create` is the label of the button leading to the `link` URL, e.g. of the page of a new item;
 * `actions` is the ID of the form of the bulk actions; `views` are links to the parts of the list after the title,
 * a list of `['title' => ..., 'query' => [...], 'active' => bool]`, where `query` overrides the current GET parameters
 * and null removes one; an item with `click` is a button instead.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/header.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$title, $badge, $content, $uploader, $filter, $filters, $actions, $search, $translation, $project, $create, $link, $views] = Safe::data($__data ?? [], [
    'title'       => 'trim',
    'badge'       => 'trim',
    'content'     => 'trim',
    'uploader'    => 'bool:false',
    'filter'      => 'bool:false',
    'filters'     => 'array',
    'actions'     => 'trim',
    'search'      => 'trim',
    'translation' => 'bool:false',
    'project'     => 'trim',
    'create'      => 'trim',
    'link'        => 'trim',
    'views'       => 'array',
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

    @if ($views)
        <div class="table-header-views">
            @foreach ($views as $part)
                @if (empty($part['click']))
                    <a class="{{ empty($part['active']) ? '' : 'is-active' }}" href="?{{ $part['link'] ?? '' }}">
                        {{ $part['title'] ?? '' }}
                    </a>
                @else
                    <button class="btn btn--xs t-red" type="button" @click="{{ $part['click'] }}">{{ $part['title'] ?? '' }}</button>
                @endif
            @endforeach
        </div>
    @endif

    <div class="table-header-group" u-show="!bulk">
        @if ($filter)
            <?php
            echo Form::view('number', [
                'type'       => 'number',
                'name'       => 'page',
                'class'      => 'field field--raised',
                'attributes' => ['min' => 0, 'value' => 3],
            ]);
            ?>
        @endif

        @if ($search)
            <?php
            echo Form::view('input', [
                'type'       => 'search',
                'name'       => 's',
                'class'      => 'field field--raised',
                'attributes' => ['type' => 'search', 'name' => 's', 'placeholder' => $search],
            ]);
            ?>
        @endif

        @if ($uploader)
            <button class="btn btn--outline" type="button" @click="$dialog.open('tmpl-media-uploader', uploaderDialog)">
                <i class="ph ph-upload-simple"></i> {{ t('Add New File') }}
            </button>
        @endif

        @if ($create && $link)
            <a class="btn btn--primary" href="{{ $link }}">
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

    @if ($actions)
        <div class="table-header-group" u-show="bulk" hidden>
            <?php echo form($actions); ?>
        </div>
    @endif
</div>

@if ($filters)
    <?php echo view('parts/table/filter', ['filters' => $filters]); ?>
@endif

{!! $content !!}
