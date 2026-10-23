<?php

use Expansa\Facades\Hook;

/**
 * Files storage template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/media.php
 *
 * @var App\Tables\Media $table
 *
 * @package Expansa\Templates
 */

Hook::add('renderDashboardFooter', function () {
    echo view('parts/dialogs/media-editor');
    echo view('parts/dialogs/media-uploader');
}, 0);
?>
<div class="storage" u-data="{items: [], selected: []}" u-dragselect="selected = [...new Set([...selected, ...$event.detail.ids])]">
    <div class="df aic g-3" u-show="selected.length" hidden>
        <span class="fs-13"><span u-text="selected.length"></span> <?php echo t('selected'); ?></span>
        <button type="button" class="btn btn--sm btn--outline" @click="selected = []"><?php echo t('Cancel'); ?></button>
        <button type="button" class="btn btn--sm btn--outline" @click="$ajax.post('media/delete', {ids: selected}).then(({deleted}) => { items = items.filter(item => !deleted.includes(item.id)); selected = []; })"><?php echo t('Delete Selected'); ?></button>
    </div>

    <div class="storage__items">
        <div class="storage__item" :class="selected.includes(item.id) && 'is-active'" :data-id="item.id" u-each.lazy="(item, i) in items" @click="$dialog.xl.open('tmpl-media-editor', item)">
            <img class="storage__image" u-show="item.url || item.icon" :src="item.sizes?.thumbnail?.url || item.url || item.icon || ''" width="200" height="200" alt>
            <div class="storage__meta" u-show="item.url || item.icon">
                <div class="storage__data" u-text="item.sizeHumanize ?? ''"></div>
            </div>
            <img class="storage__image" u-show="!item.url && !item.icon" src="{{ url('/dashboard/assets/images/files/broken.svg') }}" title="{{ t('Broken image') }}" width="40" height="40" alt>
        </div>
    </div>

    <div u-show="items.length === 0" @load="console.log(items.length)">
        <?php echo view('parts/state', $table->notFoundData()); ?>
    </div>

    <div @intersect="$ajax.post('media/get', '', ({posts}) => items = posts)"></div>
</div>
