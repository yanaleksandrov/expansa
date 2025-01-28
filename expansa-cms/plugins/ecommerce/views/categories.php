<?php

use Expansa\Builders\Form;

/**
 * Attributes list
 *
 * This template can be overridden by copying it to themes/yourtheme/ecommerce/templates/attributes.php
 *
 * @package Expansa\Templates
 */
?>
<div class="expansa-main">
    <div class="attributes">
        <form class="attributes-wrapper" x-data="{values: []}">
            <div class="attributes-editor">
                <h5 class="attributes-title">
                    <span class="fw-600 mr-auto"><?php echo t('Products Categories'); ?></span>
                    <button class="btn btn--sm" type="button"><?php echo t('Export'); ?></button>
                    <button class="btn btn--sm" type="button"><?php echo t('Import'); ?></button>
                    <button class="btn btn--sm btn--primary" type="submit"><?php echo t('Save'); ?></button>
                </h5>
                <div class="attributes-description">
                    <p><?php echo t('Product categories for your store can be managed here. To change the order of categories on the front-end you can drag and drop to sort them. Deleting a category does not delete the products in that category.'); ?></p>
                </div>
                <?php Form::make(EX_PLUGINS . 'ecommerce/core/categories.php', true); ?>
            </div>
            <div class="attributes-side">
                <div x-text="`<?php echo t_attr(':valuesCount items', '${values.length}'); ?>`">0 items</div>
                <div class="attributes-list">
                    <div class="attributes-values">
                        <template x-if="values.length">
                            <template x-for="(value, i) in values" :key="i">
                                <a class="attributes-value">
                                    <span class="attributes-value-title" x-text="`values.${i}.title`"></span>
                                    <span class="attributes-value-slug" x-text="`values.${i}.slug`"></span>
                                    <div class="btn btn--icon" @click="values.splice(i, 1)"><i class="ph ph-pen"></i></div>
                                </a>
                            </template>
                        </template>
                        <template x-if="!values.length">
                            <?php
                            echo view(
                                'global/state',
                                [
                                    'icon'        => 'empty-pack',
                                    'class'       => 'dg jic m-auto t-center p-8 mw-320',
                                    'title'       => t('Categories not found'),
                                    'description' => t('Try to add new category, there will be results here'),
                                ]
                            );
                            ?>
                        </template>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
