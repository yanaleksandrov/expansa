<?php

use Expansa\Facades\Safe;
use Expansa\Support\Arr;

/**
 * Media field template can be overridden by copying it to themes/yourtheme/dashboard/views/fields/media.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

[$type, $name, $label, $label_class, $class, $description, $attributes, $tooltip] = Safe::data(
    $__data ?? [],
    [
        'type'        => 'id:text',
        'name'        => 'attribute|id',
        'label'       => 'trim:field',
        'label_class' => 'class:df aic jcsb fw-500',
        'class'       => 'class:dg g-1',
        'description' => 'trim',
        'attributes'  => 'array',
        'tooltip'     => 'trim|attribute',
    ]
)->values();

$multiple = Safe::bool($attributes['multiple'] ?? false);

$attributes['type'] = 'file';
unset($attributes['multiple']);
$attributes['multiple'] = $multiple;

// Uploading (or picking) a new file replaces the current one for a single-item field,
// and is appended - skipping anything already attached - for a "gallery"-style field.
$appendUploaded = $multiple
    ? "{$name} = [...{$name}, ...(posts || [])]"
    : "{$name} = (posts || []).slice(0, 1)";
$appendSelected = $multiple
    ? "{$name} = [...{$name}, ...items.filter(item => !{$name}.some(existing => existing.id === item.id))]"
    : "{$name} = items.slice(0, 1)";
?>
<div class="<?php echo $class; ?>" u-data="{<?php echo $name; ?>: []}">
    <?php if ($label) : ?>
        <span class="<?php echo $label_class; ?>">
            <?php Safe::html($label); ?>
            <?php if ($tooltip) : ?>
                <i class="ph ph-info" u-tooltip.click.prevent="'<?php echo $tooltip; ?>'"></i>
            <?php endif; ?>
        </span>
    <?php endif; ?>

    <div class="storage storage--field" u-show="<?php echo $name; ?>.length">
        <div class="storage__item" u-each.lazy="(item, i) in <?php echo $name; ?>">
            <input type="hidden" name="<?php echo $name; ?>[]" :value="item.id">
            <template u-if="item.url || item.icon">
                <img class="storage__image" :src="item.sizes?.thumbnail?.url || item.url || item.icon" width="200" height="200" alt>
            </template>
            <span class="storage__remove" @click.stop.prevent="<?php echo $name; ?> = <?php echo $name; ?>.filter((removed, index) => index !== i)" title="<?php echo t('Remove'); ?>">
                <i class="ph ph-x"></i>
            </span>
        </div>
    </div>

    <div class="df g-2">
        <label class="btn btn--outline dif aic g-1">
            <i class="ph ph-upload-simple"></i> <?php echo t('Upload'); ?>
            <input<?php echo Arr::toHtmlAtts($attributes); ?> hidden @change="$ajax.post('media/upload', $el.files).then(({posts}) => { <?php echo $appendUploaded; ?>; $el.value = ''; })">
        </label>
        <button type="button" class="btn btn--outline dif aic g-1" @click="$dialog.open('tmpl-media-library', { ...mediaLibraryDialog, multiple: <?php echo $multiple ? 'true' : 'false'; ?>, onSelect(items) { items = Array.isArray(items) ? items : (items ? [items] : []); <?php echo $appendSelected; ?>; } })">
            <i class="ph ph-image"></i> <?php echo t('Choose from library'); ?>
        </button>
    </div>

    <?php if ($description) : ?>
        <div class="fs-13 t-muted lh-xs"><?php echo $description; ?></div>
    <?php endif; ?>
</div>
