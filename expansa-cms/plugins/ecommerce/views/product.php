<?php

/**
 * Single product editor.
 *
 * This template can be overridden by copying it to themes/yourtheme/ecommerce/templates/product.php
 *
 * @package Expansa\Templates
 */

?>
<div class="expansa-main">
    <div class="attributes">
        <form class="attributes-wrapper" u-data="{attributes: []}">
            <div class="attributes-editor">
                <h5 class="attributes-title">
                    <a class="btn btn--icon btn--sm" href="<?php echo url('/dashboard/orders'); ?>">
                        <i class="ph ph-arrow-left"></i>
                    </a>
                    <span class="fw-600 mr-auto"><?php echo t('Order :orderNumber details', '#10566'); ?></span>
                    <button class="btn btn--sm btn--primary" type="submit"><?php echo t('Save'); ?></button>
                </h5>
                <div class="attributes-description">
                    <p>Updated by Ian Iskenderov December 23, 10:14 pm</p>
                </div>

                <div class="product-repeater" u-data="repeater(expansa?.bundle ?? [])">
                    <template u-for="(group, i) in groups">
                        <div class="product-repeater-group">
                            <div class="product-repeater-title">
                                <input
                                    type="text"
                                    class="subtitle"
                                    :name="`<?php echo esc_attr($section_title_attr); ?>`"
                                    u-model="group.name"
                                    placeholder="<?php t('Subtitle'); ?>"
                                >
                                <button type="button" class="button button-small" @click="removeGroup(i)">
                                    <?php t('Remove Subgroup'); ?>
                                </button>
                            </div>

                            <template u-for="(item, key) in group.items">
                                <div class="product-repeater-row" data-condition="<?php t('AND'); ?>">
                                    <template u-for="(product, productIdx) in item.products">
                                        <div class="product-repeater-item" data-condition="<?php t('OR'); ?>">
                                            <span class="mix" u-show="product.mix.length !== 0"><?php t('MIX'); ?>:</span>
                                            <input
                                                type="hidden"
                                                :value="product.id"
                                                :name="`<?php echo esc_attr($section_name_attr); ?>[id]`"
                                            >
                                            <img class="product-repeater-item-image" :src="product.image" :alt="product.title" u-show="product.mix.length === 0">
                                            <input
                                                type="text"
                                                u-model="product.shortTitle"
                                                :name="`<?php echo esc_attr($section_name_attr); ?>[shortTitle]`"
                                                placeholder="<?php t('Product Short Title'); ?>"
                                            >
                                            <a class="product-repeater-item-name" target="_blank" u-text="product.title" :href="product.permalink" u-show="product.mix.length === 0"></a>
                                            <input
                                                min="1"
                                                max="99"
                                                step="1"
                                                type="number"
                                                u-model="product.qty"
                                                u-show="product.mix.length === 0"
                                                :name="`<?php echo esc_attr($section_name_attr); ?>[qty]`"
                                                title="<?php t('Default Quantity'); ?>"
                                                required
                                            >
                                            <span
                                                class="product-repeater-item-price"
                                                u-html="` × ${product.price}`"
                                                u-show="product.mix.length === 0"
                                            ></span>
                                            <span class="button" @click="joinProduct(product)" u-show="product.mix.length === 0">
                                                <?php t('MIX'); ?>
                                            </span>
                                            <span
                                                class="button"
                                                @click="removeProduct(i, key, productIdx)"
                                                title="<?php t('Remove Product'); ?>"
                                            >×</span>
                                            <span
                                                class="button"
                                                u-show="productIdx > 0"
                                                @click="moveUp(item.products, productIdx)"
                                                title="<?php t('Move Product Up'); ?>"
                                            >&#8963;</span>
                                            <span
                                                class="button"
                                                u-show="productIdx === 0 && key > 0"
                                                @click="moveUp(group.items, key)"
                                                title="<?php t('Move Group Up'); ?>"
                                            >&#708;</span>

                                            <template u-if="product.mix.length > 0">
                                                <div class="product-repeater-item-joined">
                                                    <template u-for="(mixedProduct, mixedProductIdx) in product.mix">
                                                        <div class="product-repeater-item">
                                                            <input
                                                                type="hidden"
                                                                :value="mixedProduct.id"
                                                                :name="`<?php echo esc_attr($section_name_attr); ?>[mix][${mixedProductIdx}][id]`"
                                                            >
                                                            <img class="product-repeater-item-image" :src="mixedProduct.image" :alt="mixedProduct.title">
                                                            <input
                                                                type="text"
                                                                u-model="mixedProduct.shortTitle"
                                                                :name="`<?php echo esc_attr($section_name_attr); ?>[mix][${mixedProductIdx}][shortTitle]`"
                                                                placeholder="<?php t('Product Short Title'); ?>"
                                                            >
                                                            <a class="product-repeater-item-name" target="_blank" u-text="mixedProduct.title" :href="mixedProduct.permalink"></a>
                                                            <input
                                                                min="1"
                                                                max="99"
                                                                step="1"
                                                                type="number"
                                                                u-model="mixedProduct.qty"
                                                                :name="`<?php echo esc_attr($section_name_attr); ?>[mix][${mixedProductIdx}][qty]`"
                                                                title="<?php t('Default Quantity'); ?>"
                                                                required
                                                            >
                                                            <span class="product-repeater-item-price" u-html="` × ${mixedProduct.price}`"></span>
                                                            <span class="button" @click="removeMixedProduct(product, mixedProductIdx)" title="<?php t('Remove Product'); ?>">×</span>
                                                        </div>
                                                    </template>
                                                    <div class="product-repeater-search" @click.outside="product.search = []">
                                                        <input
                                                            type="search"
                                                            @blur="$el.value = ''"
                                                            @focus="searchProduct($event, $el, product)"
                                                            @input.debounce.250ms="searchProduct($event, $el, product)"
                                                            data-include="<?php echo esc_attr(implode(',', $section_products_ids)); ?>"
                                                            placeholder="<?php t('Type for join product to the mix&hellip;'); ?>"
                                                        >
                                                        <template u-if="product.search.length > 0">
                                                            <div class="product-repeater-search-box">
                                                                <template u-for="data in product.search">
                                                                    <div class="product-repeater-search-item" @click="addMixedProduct(product, data)">
                                                                        <img class="product-repeater-image" :src="data.image" :alt="data.title">
                                                                        <span class="product-repeater-name" u-text="data.title"></span>
                                                                        <span class="product-repeater-price" u-html="data.price"></span>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <div class="product-repeater-search" @click.outside="item.search = []">
                                        <input
                                            type="search"
                                            @blur="$el.value = ''"
                                            @focus="searchProduct($event, $el, item)"
                                            @input.debounce.250ms="searchProduct($event, $el, item)"
                                            data-include="<?php echo esc_attr(implode(',', $section_products_ids)); ?>"
                                            placeholder="<?php t('Type for add new product&hellip;'); ?>"
                                        >
                                        <template u-if="item.search.length > 0">
                                            <div class="product-repeater-search-box">
                                                <template u-for="data in item.search">
                                                    <div class="product-repeater-search-item" @click="addProduct(item, data)">
                                                        <img class="product-repeater-image" :src="data.image" :alt="data.title">
                                                        <span class="product-repeater-name" u-text="data.title"></span>
                                                        <span class="product-repeater-price" u-html="data.price"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div class="button button-small" @click="addItem(group)"><?php t('AND'); ?></div>
                        </div>
                    </template>
                    <button type="button" class="product-repeater-add button button-small" @click="addGroup">
                        <?php t('Add New Subgroup'); ?>
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
