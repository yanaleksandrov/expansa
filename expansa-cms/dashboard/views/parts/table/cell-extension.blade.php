<?php
/**
 * Card of a plugin from the repository: screenshot, description, authors, category and stats.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-extension.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Extension $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="plugins__item">
    @if ($value->category)
        <div class="plugins__pretitle"><i class="ph ph-bag"></i> {{ $value->category }}</div>
    @endif
    <div class="plugins__card">
        <div class="plugins__image" style="background-image: url({{ $value->screenshot }})"></div>
        <div class="plugins__data">
            <h4 class="plugins__title">{{ $value->title }}</h4>
            <div class="plugins__description">{{ $value->description }}</div>
            @if ($value->authors)
                <div class="plugins__author">
                    {{ t('by') }}
                    @foreach ($value->authors as $i => $author)
                        {{ $i > 0 ? ',' : '' }} <a href="{{ $author->url ?: '#' }}" target="_blank">{{ $author->title }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="plugins__action">
            @if (! $value->installed || ! $value->active)
                <button class="btn btn--outline" type="button">{{ t('Install') }}</button>
            @endif
            @if (! $value->installed || $value->active)
                <button class="btn btn--primary" type="button">{{ t('Activate') }}</button>
            @endif
        </div>
    </div>
    <div class="plugins__info">
        <span class="plugins__text"><i class="ph ph-desktop-tower"></i> {{ t('Installs: :installsCount', $value->installations) }}</span>
        <span class="plugins__text"><i class="ph ph-calendar-dots"></i> {{ t('Last updated: :date', $value->date) }}</span>
        <span class="plugins__text">
            @if ($value->reviews > 0)
                <?php echo view('parts/rating', ['rating' => $value->rating, 'reviews' => $value->reviews]); ?>
            @else
                {{ t('This plugin has not been rated yet.') }}
            @endif
        </span>
        <span class="plugins__text"><i class="ph ph-check"></i> {{ t('Compatible') }}</span>
    </div>
</div>
