<?php
/**
 * Translation table template can be overridden by copying it to themes/yourtheme/dashboard/views/translation.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$table = $__data['table'] ?? null;
if (! $table instanceof Expansa\Builders\Table) {
    return;
}

echo view('table/header', $table->headData());
?>

@if($table->data)
    <form class="translation" method="POST" @input.debounce.500ms="$ajax.post('translations/update',{project})" u-data="{items: {}}">
        <div class="translation-head">
            @foreach($table->cells as $i => $cell)
                <div class="translation-{{ $i === 0 ? 'source' : 'value' }}">
                    <i class="{{ $i === 0 ? 'ph ph-text-aa' : 'ph ph-globe-hemisphere-east' }}"></i> {{ $cell->title }} - English
                </div>
            @endforeach
        </div>
        <div class="translation-grid" u-each.lazy="item in items" u-init="console.log(item)">
            <div class="translation-source" u-text="item.source">{{ $item['source'] ?? '' }}</div>
            <label class="translation-value">
                <textarea rows="1" u-textarea="7" :value="item.value">{{ $item['value'] ?? '' }}</textarea>
            </label>
        </div>
        @foreach($table->data as $item)
            <div class="translation-grid">
                <div class="translation-source">{{ $item['source'] ?? '' }}</div>
                <label class="translation-value">
                    <textarea name="`translations[${item.source}]`" rows="1" u-textarea="7">{{ $item['value'] ?? '' }}</textarea>
                </label>
            </div>
        @endforeach
    </form>
@else
    <?php echo view('global/state', $table->notFoundData()); ?>
@endif
