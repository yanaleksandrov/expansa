<?php

use Expansa\Builders\Form;

/**
 * Expansa dashboard menu template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/nav-menu.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="nav-editor">
    <div class="nav-editor-side">
        <h5 class="nav-editor-title"><?php echo t('Menus'); ?></h5>
        <?php
        echo Form::view(
            'select',
            [
                'type' => 'select',
                'name' => 'menu-editing',
                'error' => 'menu-editing',
                'label' => t('Select a Menu to Edit'),
                'class' => 'field field--sm field--raised',
                'label_class' => 'df fs-13 t-muted',
                'reset' => 0,
                'before' => '',
                'after' => '',
                'instruction' => '',
                'tooltip' => '',
                'copy' => 0,
                'validator' => '',
                'conditions' => [],
                'attributes' => [
                    'name' => 'menu-editing',
                    'u-select' => '',
                ],
                'options' => [
                    'type' => t('Top Left'),
                    'template' => t('Top Right'),
                    'status' => t('Primary'),
                    'format' => t('Sidebar'),
                ],
            ],
        );
        ?>
        <a class="btn btn--sm" href="#">{{ t('Create a New Menu') }}</a>
        <div class="accordion" u-data="{expanded: false}">
            <h5 class="nav-editor-eyebrow">{{ t('Add Menu Items') }}</h5>
            <div class="accordion-item">
                <div class="accordion-title" @click="expanded = ! expanded">Pages</div>
                <div class="accordion-panel" u-collapse="expanded" hidden>
                    content
                </div>
            </div>
        </div>
    </div>
    <div class="nav-editor-main">
        <h6 class="nav-editor-eyebrow">{{ t('Menu Structure') }}</h6>
        <div class="fs-12 t-muted mt-2">{{ t('Drag the items into the order you prefer. Click to reveal additional configuration options.') }}</div>
        <div class="nav-editor-box">
            <div class="df fww g-2">
                <?php
                echo Form::view(
                    'input',
                    [
                        'type' => 'text',
                        'name' => 'menu-name',
                        'error' => 'menu-name',
                        'label' => '',
                        'class' => '',
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
                            'name' => 'menu-name',
                            'placeholder' => t('Menu name'),
                            'required' => 1,
                        ],
                    ],
                );
                echo Form::view(
                    'select',
                    [
                        'type' => 'select',
                        'name' => 'menu-location',
                        'error' => 'menu-location',
                        'label' => '',
                        'class' => '',
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
                            'name' => 'menu-location',
                            'u-select' => '',
                            'multiple' => 1,
                            'required' => 1,
                            'placeholder' => t('Choose location'),
                        ],
                        'options' => [
                            'type' => t('Top Left'),
                            'template' => t('Top Right'),
                            'status' => t('Primary'),
                            'format' => t('Sidebar'),
                        ],
                    ],
                );
                ?>

            </div>

            <ul class="nav-editor-list">
                <li class="nav-editor-item">
                    <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 1</span>
                    <ul class="nav-editor-list">
                        <li class="nav-editor-item">
                            <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 1.1</span>
                            <ul class="nav-editor-list"></ul>
                        </li>
                        <li class="nav-editor-item">
                            <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 1.2</span>
                            <ul class="nav-editor-list"></ul>
                        </li>
                    </ul>
                </li>
                <li class="nav-editor-item">
                    <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 2</span>
                    <ul class="nav-editor-list"></ul>
                </li>
                <li class="nav-editor-item">
                    <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3</span>
                    <ul class="nav-editor-list">
                        <li class="nav-editor-item">
                            <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3.1</span>
                            <ul class="nav-editor-list"></ul>
                        </li>
                        <li class="nav-editor-item">
                            <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3.2</span>
                            <ul class="nav-editor-list"></ul>
                        </li>
                    </ul>
                </li>
                <li class="nav-editor-item">
                    <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 4</span>
                    <ul class="nav-editor-list">
                        <li class="nav-editor-item">
                            <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3.1</span>
                            <ul class="nav-editor-list"></ul>
                        </li>
                        <li class="nav-editor-item">
                            <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3.2</span>
                            <ul class="nav-editor-list">
                                <li class="nav-editor-item">
                                    <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3.1</span>
                                    <ul class="nav-editor-list"></ul>
                                </li>
                                <li class="nav-editor-item">
                                    <span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> Item 3.2</span>
                                    <ul class="nav-editor-list"></ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <div class="nav-editor-tools">
        <?php
        echo Form::view(
            'description-list',
            [
                'type' => 'description-list',
                'name' => 'menu-editing',
                'error' => 'menu-editing',
                'label' => t('Select a Menu to Edit'),
                'class' => 'field field--sm field--raised',
                'label_class' => '',
                'reset' => 0,
                'before' => '',
                'after' => '',
                'instruction' => '',
                'tooltip' => '',
                'copy' => 0,
                'validator' => '',
                'conditions' => [],
                'attributes' => [],
                'options' => [],
            ],
        );
        ?>
        <div class="nav-editor-actions">
            <button class="btn btn--sm btn--primary" type="button">
                <i class="ph ph-copy"></i> {{ t('Save Menu') }}
            </button>
            <button class="btn btn--sm btn t-red" type="button">
                <i class="ph ph-trash"></i> {{ t('Delete Permanently') }}
            </button>
        </div>
    </div>
</div>
