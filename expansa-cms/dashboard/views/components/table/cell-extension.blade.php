<?php
/**
 * Card of a plugin from the repository: screenshot, description, authors, category and stats.
 * The row is `title`, `description`, `screenshot`, `author` and `categories` as links lists,
 * `installed`, `active`, `installations`, `date`, `reviews`, `rating`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-extension.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$installed = ! empty($item['installed']);
$active    = ! empty($item['active']);
$reviews   = (int) ($item['reviews'] ?? 0);
$category  = $item['categories'][0]['title'] ?? '';
?>
<div class="plugins__item">
    @if ($category !== '')
        <div class="plugins__pretitle"><i class="ph ph-bag"></i> {{ $category }}</div>
    @endif
    <div class="plugins__card">
        <div class="plugins__image" style="background-image: url({{ $item['screenshot'] ?? '' }})"></div>
        <div class="plugins__data">
            <h4 class="plugins__title">{{ $item['title'] ?? '' }}</h4>
            <div class="plugins__description">{{ $item['description'] ?? '' }}</div>
            @if (! empty($item['author']))
                <div class="plugins__author">
                    {{ t('by') }}
                    @foreach ($item['author'] as $i => $author)
                        {{ $i > 0 ? ',' : '' }} <a href="{{ $author['href'] ?? '#' }}" target="_blank">{{ $author['title'] ?? '' }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="plugins__action">
            @if (! $installed || ! $active)
                <button class="btn btn--outline" type="button">{{ t('Install') }}</button>
            @endif
            @if (! $installed || $active)
                <button class="btn btn--primary" type="button">{{ t('Activate') }}</button>
            @endif
        </div>
    </div>
    <div class="plugins__info">
        <span class="plugins__text"><i class="ph ph-desktop-tower"></i> {{ t('Installs: :installsCount', $item['installations'] ?? '') }}</span>
        <span class="plugins__text"><i class="ph ph-calendar-dots"></i> {{ t('Last updated: :date', $item['date'] ?? '') }}</span>
        <span class="plugins__text">
            @if ($reviews > 0)
                <?php echo view('components/rating', ['class' => 'df aic g-1', 'rating' => (float) ($item['rating'] ?? 0), 'reviews' => $reviews]); ?>
            @else
                {{ t('This plugin has not been rated yet.') }}
            @endif
        </span>
        <span class="plugins__text"><i class="ph ph-check"></i> {{ t('Compatible') }}</span>
    </div>
</div>
