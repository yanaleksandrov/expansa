<?php

use Expansa\Facades\Hook;

/**
 * Files storage template can be overridden by copying it to themes/yourtheme/dashboard/views/media.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$table = $__data['table'] ?? null;
if (! $table instanceof Expansa\Builders\Table) {
    return;
}

Hook::add('renderDashboardFooter', function () {
    echo view('dialogs/media-editor');
}, 0);
?>
<div class="storage" u-data="{items: [], selected: []}" u-dragselect="selected = [...new Set([...selected, ...$event.detail.ids])]">
    <div class="storage__head">
        <h4>{{ t('Media Library') }}</h4>
        <button class="btn btn--sm btn--outline" @click=""><i class="ph ph-upload-simple"></i> {{ t('Add new file') }}</button>
    </div>

    <div class="storage__uploader" hidden>
        <?php echo view('dialogs/media-uploader'); ?>
    </div>

    <div class="df aic g-3" u-show="selected.length" hidden>
        <span class="fs-13"><span u-text="selected.length"></span> <?php echo t('selected'); ?></span>
        <button type="button" class="btn btn--sm btn--outline" @click="selected = []"><?php echo t('Cancel'); ?></button>
        <button type="button" class="btn btn--sm btn--outline" @click="$ajax.post('media/delete', {ids: selected}).then(({deleted}) => { items = items.filter(item => !deleted.includes(item.id)); selected = []; })"><?php echo t('Delete selected'); ?></button>
    </div>

    <div class="storage__items">
        <div class="storage__item" :class="selected.includes(item.id) && 'active'" :data-id="item.id" u-each.lazy="(item, i) in items" @click="$dialog.open('tmpl-media-editor', item)">
            <template u-if="item.url || item.icon">
                <img class="storage__image" :src="item.sizes?.thumbnail?.url || item.url || item.icon" width="200" height="200" alt>
                <div class="storage__meta">
                    <div class="storage__data" u-text="item.sizeHumanize"></div>
                </div>
            </template>
            <template u-if="!item.url && !item.icon">
                <img class="storage__image" src="{{ url('/dashboard/assets/images/files/broken.svg') }}" title="{{ t('Broken image') }}" width="40" height="40" alt>
            </template>
        </div>

        @foreach($table->data as $item)
            <div class="storage__item" @click="$dialog.open('tmpl-media-editor', item)">
                <img class="storage__image" src="{{ $item['sizes']['thumbnail']['url'] ?? $item['url'] ?? $item['icon'] ?? '' }}" width="200" height="200" alt>
                <div class="storage__meta">
                    <div class="storage__data">{{ $item['sizeHumanize'] ?? '' }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div u-show="items.length === 0" @load="console.log(items.length)">
        <?php echo view('global/state', $table->notFoundData()); ?>
    </div>

    <div @intersect="$ajax.post('media/get', '', ({posts}) => items = posts)"></div>
</div>
