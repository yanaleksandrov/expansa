<?php

use Expansa\Builders\Form;
use Expansa\Extensions\Plugin;
use Expansa\Extensions\Theme;
use Expansa\Facades\Extensions;
use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Header above a table: the title with a badge, the tools and the bulk actions shown while rows are selected.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/header.blade.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$title, $badge, $content, $uploader, $filter, $actions, $search, $translation, $project] = Safe::data($__data ?? [], [
    'title'       => 'trim',
    'badge'       => 'trim',
    'content'     => 'trim',
    'uploader'    => 'bool:false',
    'filter'      => 'bool:false',
    'actions'     => 'bool:false',
    'search'      => 'trim',
    'translation' => 'bool:false',
    'project'     => 'trim',
])->values();

$progress = static fn (string $name) => [
    'content'     => $name,
    'description' => t(':percent% complete', 0),
];
?>
<div class="mw df fww aib jcsb g-3 py-5 px-6 sm:p-4">
    @if ($title)
        <h4>
            {!! $title !!}
            @if ($badge)
                <span class="badge">{!! $badge !!}</span>
            @endif
        </h4>
    @endif

    <div class="df aic g-1">
        <?php
        echo Form::view('input', [
            'type'       => 'input',
            'name'       => 'terminal',
            'class'      => 'field field--plain',
            'before'     => '<i class="ph ph-terminal"></i>',
            'attributes' => [
                'type'        => 'search',
                'name'        => 's',
                'placeholder' => t('Describe what you want...'),
            ],
        ]);
        ?>
    </div>

    <div class="df aic g-1">
        <div class="df aic g-1" u-show="!bulk">
            @if ($filter)
                <div class="df aic g-1">
                    <button class="btn btn--outline" type="reset" form="expansa-items-filter" @click="showFilter = !showFilter" :class="showFilter && 't-red'" :title="showFilter ? '{{ t('Reset filter') }}' : '{{ t('Filter') }}'">
                        <i class="ph ph-funnel" :class="showFilter ? 'ph-funnel-x' : 'ph-funnel'"></i>
                        <span u-text="showFilter ? '{{ t('Reset') }}' : '{{ t('Filter') }}'">{{ t('Filter') }}</span>
                    </button>
                    <?php
                    echo Form::view('number', [
                        'type'       => 'number',
                        'name'       => 'page',
                        'class'      => 'field field--raised',
                        'attributes' => ['min' => 0, 'value' => 3],
                    ]);
                    ?>
                </div>
            @endif

            @if ($search)
                <div class="df aic g-1">
                    <?php
                    echo Form::view('input', [
                        'type'       => 'search',
                        'name'       => 's',
                        'class'      => 'field field--raised',
                        'attributes' => ['type' => 'search', 'name' => 's', 'placeholder' => $search],
                    ]);
                    ?>
                </div>
            @endif

            @if ($uploader)
                <div class="df aic g-1">
                    <button class="btn btn--outline" type="button" @click="$dialog.open('tmpl-media-uploader', uploaderDialog)">
                        <i class="ph ph-upload-simple"></i> {{ t('Add New File') }}
                    </button>
                </div>
            @endif

            @if ($translation)
                <form class="df aic g-2" method="GET">
                    <input type="hidden" name="table" value="translation">
                    <div class="df aic g-1">
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
            <div class="df aic g-1" u-show="bulk" hidden>
                <?php echo form('posts-actions'); ?>
                <button type="button" class="btn t-red" u-bind="reset">
                    <i class="ph ph-trash"></i> {{ t('Reset') }}
                </button>
            </div>
        @endif
    </div>
</div>
{!! $content !!}
