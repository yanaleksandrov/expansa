<?php

/**
 * Documentation creator.
 *
 * This template can be overridden by copying it to themes/yourtheme/toolkit/templates/docify.php
 *
 * @since 2025.1
 */

if (! defined('EX_PATH')) {
    exit;
}

/**
 * Get all uploaded plugins.
 *
 * @since 2025.1
 */
$list    = [];

return \Expansa\Facades\Form::enqueue(
    'import/documents',
    [
        'class'           => 'card card-border',
        '@submit.prevent' => '$ajax.post("import/documents", "", response => {completed = response})',
        'u-data'          => '{project:"",completed:""}',
    ],
    [
        [
            'name'     => 'title',
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <div class="progress" :style="'--expansa-progress:' + progress().progress"></div>
                <div class="p-8 pt-7 pb-7 df aic jcsb">
                    <span u-text="current().title"><?php echo t('Choose project'); ?></span>
                    <span class="t-muted">
                        step <strong u-text="progress().current">1</strong> from <strong u-text="progress().total">2</strong>
                    </span>
                </div>
                <div class="card-hr"></div>
                <?php
            },
        ],
        [
            'type'       => 'step',
            'attributes' => [
                'class'          => 'pl-8 pr-8',
                'u-wizard:title' => t('Choose project'),
            ],
            'fields'     => [
                [
                    'name'        => 'title',
                    'type'        => 'header',
                    'class'       => 'p-8 t-center',
                    'label'       => t('Select the project you want to export to docs'),
                    'instruction' => t('This tool allows you to convert docblock comments into docs pages. You can also use markdown.'),
                ],
                [
                    'type'        => 'select',
                    'label'       => t('Select a project to document'),
                    'name'        => 'project',
                    'value'       => 'none',
                    'placeholder' => '',
                    'class'       => '',
                    'reset'       => 0,
                    'required'    => 0,
                    'before'      => '',
                    'after'       => '',
                    'tooltip'     => '',
                    'instruction' => '',
                    'attributes'  => [],
                    'conditions'  => [],
                    'options'     => [
                        'optgroup' => [
                            'label'   => t('Plugins'),
                            'options' => [
                                'none' => t('Nothing is selected'),
                                ...$list,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'       => 'step',
            'attributes' => [
                'class'          => 'pl-8 pr-8',
                'hidden'        => true,
                'u-wizard:title' => t('Project import is completed'),
            ],
            'fields'     => [
                [
                    'type'     => 'custom',
                    'callback' => fn () => '<div class="dg" u-html="completed"></div>',
                ],
            ],
        ],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <!-- buttons -->
                <div class="p-8 df jcfe g-2">
                    <button type="submit" class="btn btn--primary" :disabled="project.trim() === 'none'" disabled><?php echo t('Run the importer'); ?></button>
                </div>
                <?php
            },
        ],
    ]
);
?>
<div class="p-7">
    <div class="mw-600 m-auto">
        <?php echo form('documents', EX_DASHBOARD . 'import/documents.php'); ?>
    </div>
</div>
