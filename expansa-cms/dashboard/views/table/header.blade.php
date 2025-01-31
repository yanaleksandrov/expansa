<?php

use Expansa\Facades\I18n;
use Expansa\Facades\Safe;

/**
 * Table header
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/table/header.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

[$title, $badge, $show, $content, $uploader, $filter, $actions, $search, $translation] = Safe::data(
    $__data ?? [],
    [
        'title' => 'trim',
        'badge' => 'trim',
        'show' => 'bool:true',
        'content' => 'trim',
        'uploader' => 'bool:false',
        'filter' => 'bool:false',
        'actions' => 'bool:false',
        'search' => 'bool:false',
        'translation' => 'bool:false',
    ]
)->values();
?>
        <!-- table head start -->
<div class="table__header">
    <div class="mw df fww aic jcsb g-3 py-5 px-7 md:p-5">
        <?php if ($title) : ?>
        <h4><?php echo $title; ?>
                <?php $badge && print('<span class="badge">' . $badge . '</span>'); ?>
        </h4>
        <?php endif; ?>
        <div class="df aic g-1">
            <div class="df aic g-1" x-show="!bulk">
                <?php if ($filter) : ?>
                <div class="df aic g-1">
                    <button class="btn btn--sm btn--outline" type="reset" form="expansa-items-filter"
                            @click="showFilter = !showFilter" :class="showFilter && 't-red'"
                            :title="showFilter ? '<?php echo t_attr( 'Reset Filter' ); ?>' : '<?php echo t( 'Filter' ); ?>'">
                        <i class="ph ph-funnel" :class="showFilter ? 'ph-funnel-x' : 'ph-funnel'"></i>
                        <span x-text="showFilter ? '<?php echo t_attr( 'Reset' ); ?>' : '<?php echo t_attr( 'Filter' ); ?>'"><?php echo t('Filter'); ?></span>
                    </button>
                        <?php
                        echo view(
                            'form/number',
                            [
                                'type' => 'number',
                                'name' => 'page',
                                'label' => '',
                                'class' => 'field field--sm field--outline',
                                'label_class' => '',
                                'reset' => 0,
                                'before' => '',
                                'after' => '',
                                'instruction' => '',
                                'tooltip' => '',
                                'copy' => 0,
                                'validator' => '',
                                'conditions' => [],
                                'attributes' => [
                                    'min' => 0,
                                    'value' => 3,
                                ],
                            ]
                        );
                        ?>
                </div>
                <?php endif; ?>
                <?php if ($search) : ?>
                <div class="df aic g-1">
                        <?php
                        echo view(
                            'form/input',
                            [
                                'type' => 'search',
                                'name' => 's',
                                'label' => '',
                                'class' => 'field field--sm field--outline',
                                'label_class' => '',
                                'reset' => 0,
                                'before' => '',
                                'after' => '',
                                'instruction' => '',
                                'tooltip' => '',
                                'copy' => 0,
                                'validator' => '',
                                'conditions' => [],
                                'attributes' => [
                                    'type' => 'search',
                                    'name' => 's',
                                    'placeholder' => t('Search plugins'),
                                ],
                            ]
                        );
                        ?>
                </div>
                <?php endif; ?>
                <?php if ($uploader) : ?>
                <div class="df aic g-1">
                    <button class="btn btn--sm btn--outline"
                            @click="$dialog.open('tmpl-media-uploader', uploaderDialog)">
                        <i class="ph ph-upload-simple"></i> <?php echo t('Add new file'); ?>
                    </button>
                </div>
                <?php endif; ?>
                <?php if ($translation) : ?>
                <div class="df aic g-2">
                    <div class="df aic g-1">
                        <svg width="16" height="16">
                            <use xlink:href="<?php echo url('/dashboard/assets/sprites/flags.svg#us'); ?>"></use>
                        </svg>
                        English
                    </div>
                    <span class="badge badge--round badge--icon badge--lg"><i
                                class="ph ph-arrows-left-right"></i></span>
                        <?php
                        echo view(
                            'form/select',
                            [
                                'type' => 'select',
                                'name' => 'language',
                                'label' => '',
                                'class' => 'field field--sm field--outline',
                                'label_class' => '',
                                'reset' => 0,
                                'before' => '',
                                'after' => '',
                                'instruction' => '',
                                'tooltip' => '',
                                'copy' => 0,
                                'validator' => '',
                                'conditions' => [],
                                'attributes' => [
                                    'x-select' => '',
                                    'name' => 'language',
                                ],
                                'options' => I18n::getLanguagesOptions(),
                            ]
                        );

                        echo view(
                            'form/select',
                            [
                                'type' => 'select',
                                'name' => 'project',
                                'label' => '',
                                'class' => 'field field--sm field--outline',
                                'label_class' => '',
                                'reset' => 0,
                                'before' => '',
                                'after' => '',
                                'instruction' => '',
                                'tooltip' => '',
                                'copy' => 0,
                                'validator' => '',
                                'conditions' => [],
                                'attributes' => [
                                    'name' => 'project',
                                    'x-model.fill' => 'project',
                                    'x-select' => '',
                                ],
                                'options' => [
                                    'core' => [
                                        'label' => t('Core'),
                                        'options' => [
                                            'core' => [
                                                'content' => t('Expansa Core'),
                                                'description' => t('completion :percent\%', 0),
                                            ],
                                        ],
                                    ],
                                    'plugins' => [
                                        'label' => t('Plugins'),
                                        'options' => array_reduce(Expansa\Plugins::get(), function ($carry, Expansa\Plugin $plugin) {
                                            $carry[$plugin->id] = [
                                                'content' => $plugin->name,
                                                'description' => t('completion :percent%', 0),
                                            ];
                                            return $carry;
                                        }, []),
                                    ],
                                    'themes' => [
                                        'label' => t('Themes'),
                                        'options' => array_reduce(Expansa\Themes::get(), function ($carry, Expansa\Plugin $theme) {
                                            $carry[$theme->id] = [
                                                'content' => $theme->name,
                                                'description' => t('completion :percent%', 0),
                                            ];
                                            return $carry;
                                        }, []),
                                    ],
                                ],
                            ]
                        );
                        ?>
                    <button type="button" class="btn btn--sm btn--outline"
                            @click="$ajax('translations/get', {project}).then(data => items = data.items)"><i
                                class="ph ph-scan"></i> <?php echo t('Scan'); ?></button>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($actions) : ?>
            <div class="df aic g-1" x-show="bulk" x-cloak>
                    <?php echo form('posts-actions', EX_DASHBOARD . 'forms/posts-actions.php'); ?>
                <button type="button" class="btn btn--sm t-red" x-bind="reset"><i
                            class="ph ph-trash"></i> <?php echo t('Reset'); ?></button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php //Dashboard\Form::make( 'items-filter' ); ?>
    <?php $content && print($content . PHP_EOL); ?>
</div>
