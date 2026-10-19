<?php
/**
 * Translation table template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/translation.php
 *
 * @var App\Tables\Translations $table
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

echo view('parts/table/table-header', $table->headData());
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
        @foreach ($table->getData() as $i => $item)
            <div class="translation-grid">
                <div class="translation-source">
                    {{ $item['source'] }}
                    @if ($item['context'])
                        <span class="badge ml-2" title="{{ t('Context') }}">{{ $item['context'] }}</span>
                    @endif
                </div>
                <label class="translation-value">
                    <input type="hidden" name="translations[{{ $i }}][source]" value="{{ $item['source'] }}">
                    <input type="hidden" name="translations[{{ $i }}][context]" value="{{ $item['context'] }}">
                    <textarea name="translations[{{ $i }}][value]" rows="7"@if ($item['forms']) disabled title="{{ t('Plural forms are edited in the translation file.') }}"@endif>{{ $item['value'] }}</textarea>
                </label>
            </div>
        @endforeach
    </form>
@else
    <?php echo view('parts/state', $table->notFoundData()); ?>
@endif
