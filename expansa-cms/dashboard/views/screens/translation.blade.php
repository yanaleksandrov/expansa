<?php
/**
 * Translation table template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/translation.php
 *
 * @var App\Tables\Translations $table
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

echo view('components/table/header', $table->headData());
?>

@if ($table->getData())
    <form class="translation" method="POST" @input.debounce.500ms="$ajax.post('translations/update')">
        <input type="hidden" name="project" value="{{ $table->project }}">
        <div class="translation-head">
            @foreach ($table->cells as $i => $cell)
                <div class="translation-{{ $i === 0 ? 'source' : 'value' }}">
                    <i class="ph {{ $i === 0 ? 'ph-text-aa' : 'ph-globe-hemisphere-east' }}"></i> {{ $cell->title }} - English
                </div>
            @endforeach
        </div>
        @foreach ($table->getData() as $item)
            <div class="translation-grid">
                <div class="translation-source">{{ $item['source'] }}</div>
                <label class="translation-value">
                    <textarea name="translations[{{ $item['source'] }}]" rows="1" u-textarea="7">{{ $item['value'] }}</textarea>
                </label>
            </div>
        @endforeach
    </form>
@else
    <?php echo view('components/state', $table->notFoundData()); ?>
@endif
