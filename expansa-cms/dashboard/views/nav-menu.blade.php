<?php

/**
 * Expansa dashboard menu.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/nav-menu.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <div class="nav-editor">
        <div class="nav-editor-side">
            <h4><?php echo t('Menus'); ?></h4>
            <div class="dg g-2 p-4">
                <?php
                echo view(
                    'form/select',
                    [
                        'type' => 'select',
                        'name' => 'menu-editing',
                        'label' => t('Select a menu to edit'),
                        'class' => 'field field--outline',
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
                            'x-select' => '',
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
                <a class="fw-500 fs-13" href="#"><?php echo t('Create a new menu'); ?></a>
            </div>
            <h6><?php echo t('Add menu items'); ?></h6>
            <div class="accordion" x-data="{expanded: false}">
                <div class="accordion-item">
                    <div class="accordion-title" @click="expanded = ! expanded">Pages</div>
                    <div class="accordion-panel" x-show="expanded" x-collapse x-cloak>
                        content
                    </div>
                </div>
            </div>
        </div>
        <div class="nav-editor-main">
            <div class="table__header p-6 g-2">
                <h6><?php echo t('Menu structure'); ?></h6>
            </div>
            <div class="df fww g-2 p-6">
                <?php
                echo view(
                    'form/input',
                    [
                        'type' => 'text',
                        'name' => 'menu-name',
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
                            'placeholder' => t('Menu Name'),
                            'required' => 1,
                        ],
                    ],
                );
                echo view(
                    'form/select',
                    [
                        'type' => 'select',
                        'name' => 'menu-location',
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
                            'x-select' => '',
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
                echo view(
                    'form/submit',
                    [
                        'type' => 'text',
                        'name' => 'menu-create',
                        'label' => t('Create Menu'),
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
                            'class' => 'btn btn--primary',
                            'name' => 'menu-create',
                        ],
                    ],
                );
                ?>
                <div class="fs-13 t-muted"><?php echo t('Drag the items into the order you prefer. Click the arrow on the right of the item to reveal additional configuration options.'); ?></div>
            </div>
            <ul class="nav-editor-list">
                <li class="nav-editor-item">
                    <span class="nav-editor-item-text"><i
                                class="ph ph-dots-six-vertical"></i> <span>Item 1 </span></span>
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
        <div class="nav-editor-tools">
            <!--			<template x-ref="treeTemplate">-->
            <!--				<ul class="nav-editor-list" x-data="{items: [{title: 'Test 1', children:[{title: 'Test 2'}, {title: 'Test 3'}]}]}">-->
            <!--					<template x-for="item in items.children">-->
            <!--						<li class="nav-editor-item">-->
            <!--							<span class="nav-editor-item-text"><i class="ph ph-dots-six-vertical"></i> <span x-text="item.title"></span></span>-->
            <!--						</li>-->
            <!--						<template x-html="$refs.treeTemplate.innerHTML" x-data="{items: item.children}"></template>-->
            <!--					</template>-->
            <!--				</ul>-->
            <!--			</template>-->

            <!--			<template x-if="elements.length" x-data="{items:[], elements: [{title: 'Test 1', children:[]},{title: 'Test 2', children:[{title: 'Test 2.1'}, {title: 'Test 2.2'}]}]}">-->
            <!--				<ul class="nav-editor-list" x-init="items = elements.slice()">-->
            <!--					<template x-ref="treeTemplate" x-for="item in items" :key="item.title">-->
            <!--						<li class="nav-editor-item">-->
            <!--							<span class="nav-editor-item-text">-->
            <!--								<i class="ph ph-dots-six-vertical"></i>-->
            <!--								<span x-text="item.title"></span>-->
            <!--							</span>-->
            <!--							<template x-if="item.children">-->
            <!--								<ul class="nav-editor-list" x-html="$refs.treeTemplate.innerHTML" x-data="{items: item.children}"></ul>-->
            <!--							</template>-->
            <!--						</li>-->
            <!--					</template>-->
            <!--				</ul>-->
            <!--			</template>-->

            <?php echo form(EX_DASHBOARD . 'forms/menu-item-editor.php'); ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let nestedSortables = [].slice.call(document.querySelectorAll('.nav-editor-list'));
        nestedSortables.forEach(el => {
            new Sortable(el, {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
            });
        });
    });
</script>