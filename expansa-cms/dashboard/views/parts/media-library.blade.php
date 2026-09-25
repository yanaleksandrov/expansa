<?php
/**
 * The actual media library UI: toolbar (search/filter/upload), a selectable grid and a
 * details side panel for the currently focused file. Shared, unmodified, by both the
 * standalone `/dashboard/media` page (`views/media.blade.php`) and the `tmpl-media-library`
 * dialog (`views/dialogs/media-library.blade.php`) - see `Youla.data('storage', ...)` in
 * `src/js/youla-storage.js` for the component backing it and how it tells the two apart.
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="media-library" u-data="storage" @load="configure(typeof entry === 'undefined' ? null : entry); $ajax.post('media/get', {page: 1}).then(({posts}) => setItems(posts, true))">
    <div class="media-library-toolbar df aic jcsb g-3 fw p-4">
        <div class="df aic g-2 fw">
            <input type="search" class="field field--outline fs-13" placeholder="<?php echo t('Search media'); ?>"
                   @input.delay.400ms="search($el.value); $ajax.post('media/get', {page: 1, s: query}).then(({posts}) => setItems(posts, true))">

            <select class="field field--outline fs-13" :value="type" @change="setType($el.value)">
                <option value=""><?php echo t('All media items'); ?></option>
                <option value="image"><?php echo t('Images'); ?></option>
                <option value="video"><?php echo t('Video'); ?></option>
                <option value="audio"><?php echo t('Audio'); ?></option>
                <option value="application"><?php echo t('Documents'); ?></option>
            </select>

            <button type="button" class="btn btn--outline t-red fs-13" u-show="selected.length" @click="$ajax.post('media/delete', {ids: selected}).then(({deleted}) => removeDeleted(deleted))">
                <i class="ph ph-trash"></i> <?php echo t('Delete selected'); ?>
            </button>
        </div>

        <div class="df aic g-2">
            <label class="btn btn--primary fs-13 dif aic g-1">
                <i class="ph ph-upload-simple"></i> <?php echo t('Upload files'); ?>
                <input type="file" multiple hidden @change="$ajax.post('media/upload', $el.files, e => percent = e.percent).then(({posts, errors}) => { addUploaded(posts, errors); $el.value = ''; })">
            </label>
        </div>
    </div>

    <div u-show="errors.length" class="dg g-1 p-4 pt-0">
        <div class="fs-13 t-red" u-each="error in errors" u-text="error.message?.join?.(' ') || error.message"></div>
    </div>

    <div class="media-library-body df">
        <div class="storage media-library-grid" u-dragselect="applyDragSelection($event.detail)">
            <div class="storage__item" :class="isSelected(item.id) && 'active'" :data-id="item.id"
                 u-each.lazy="(item, i) in visible"
                 @click="toggle(item, $event)" @dblclick="confirm(); $dialog.close()">
                <template u-if="item.url || item.icon">
                    <img class="storage__image" :src="item.sizes?.thumbnail?.url || item.url || item.icon" width="200" height="200" alt loading="lazy">
                    <div class="storage__meta">
                        <div class="storage__data" u-text="item.filename"></div>
                    </div>
                </template>
            </div>

            <div u-show="!items.length" class="p-6">
                <?php echo view('global/state', ['title' => t('No media files yet'), 'description' => t('Upload your first file to get started.')]); ?>
            </div>

            <div @intersect="hasMore && !loadingMore && (loadingMore = true, $ajax.post('media/get', {page, s: query}).then(({posts}) => setItems(posts, false)).finally(() => loadingMore = false))"></div>
        </div>

        <div class="media-editor-side media-library-side" u-show="editing()">
            <template u-if="editing()">
                <img class="media-editor-image" :src="editing().url" :alt="editing().filename" width="100%">

                <div class="dg g-1 fs-12">
                    <div><strong><?php echo t('File name'); ?>:</strong> <span u-text="editing().filename"></span></div>
                    <div><strong><?php echo t('File type'); ?>:</strong> <span u-text="editing().mime"></span></div>
                    <div><strong><?php echo t('File size'); ?>:</strong> <span u-text="editing().sizeHumanize"></span></div>
                </div>

                <div class="dg g-1">
                    <span class="fs-12 t-muted"><?php echo t('File URL'); ?></span>
                    <div class="df g-2">
                        <input type="text" class="field field--outline fs-13" readonly :value="editing().url" u-ref="fileUrl">
                        <button type="button" class="btn btn--outline" @click="$copy(editing().url)" title="<?php echo t('Copy URL to clipboard'); ?>"><i class="ph ph-copy"></i></button>
                    </div>
                </div>

                <button type="button" class="btn btn--outline t-red" @click="$ajax.post('media/delete', {ids: selected}).then(({deleted}) => removeDeleted(deleted))">
                    <i class="ph ph-trash"></i> <?php echo t('Delete permanently'); ?>
                </button>
            </template>
        </div>
    </div>

    <div class="dialog-footer media-library-footer" u-show="onSelect">
        <span class="fs-13 t-muted" u-show="selected.length"><span u-text="selected.length"></span> <?php echo t('selected'); ?></span>
        <button type="button" class="btn btn--primary" :disabled="!selected.length" @click="confirm(); $dialog.close()">
            <?php echo t('Select'); ?>
        </button>
    </div>
</div>
