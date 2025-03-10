<?php

use Expansa\Facades\Hook;

/**
 * Files storage template can be overridden by copying it to themes/yourtheme/dashboard/views/media.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = $__data['table'] ?? null;
if (! $table instanceof Expansa\Builders\Table) {
    return;
}

Hook::add('renderDashboardFooter', function () {
    echo view('dialogs/media-editor');
    echo view('dialogs/media-uploader');
}, 0);

echo view('table/header', $table->headData());
?>
<div class="storage" v-data="{items: {}}">
    <div class="storage__item" v-each.lazy="(item, i) in items" @click="$dialog.open('tmpl-media-editor', item)">
        <template v-if="item.url || item.icon">
            <img class="storage__image" :src="item.sizes?.thumbnail?.url || item.url || item.icon" width="200" height="200">
            <div class="storage__meta">
                <div class="storage__data" v-text="item.sizeHumanize"></div>
            </div>
        </template>
        <template v-if="!item.url && !item.icon">
            <img class="storage__image" src="{{ url('/dashboard/assets/images/files/broken.svg') }}" title="{{ t('Image is broken') }}" width="40" height="40">
        </template>
    </div>
    @foreach($table->data as $item)
        <div class="storage__item" @click="$dialog.open('tmpl-media-editor', item)">
            <img class="storage__image" src="{{ $item['sizes']['thumbnail']['url'] ?? $item['url'] ?? $item['icon'] ?? '' }}" width="200" height="200">
            <div class="storage__meta">
                <div class="storage__data">{{ $item['sizeHumanize'] ?? '' }}</div>
            </div>
        </div>
    @endforeach

    <div v-hide="items.length === 0">
        <?php echo view('global/state', $table->notFoundData()); ?>
    </div>

    <div x-intersect="$ajax('media/get').then(({posts}) => items = posts)"></div>
</div>
