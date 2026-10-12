<?php
/**
 * Card of a theme: screenshot with the actions, title, description, rating and version.
 * The row is `title`, `description`, `screenshot`, `installed`, `reviews`, `rating`, `version`.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/table/cell-theme.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var array<string, mixed>        $item
 * @var mixed                       $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$reviews = (int) ($item['reviews'] ?? 0);
$version = (string) ($item['version'] ?? '');
?>
<div class="themes-item">
    <div class="themes-image" style="background-image: url({{ $item['screenshot'] ?? '' }})">
        <div class="themes-action">
            <button class="btn btn--outline" type="button">{{ t('View Demo') }}</button>
            @if (empty($item['installed']))
                <button class="btn btn--outline" type="button">{{ t('Activate') }}</button>
            @else
                <button class="btn btn--primary" type="button">{{ t('Customize') }}</button>
            @endif
        </div>
    </div>
    <h6 class="themes-title">{{ $item['title'] ?? '' }}</h6>
    @if (! empty($item['description']))
        <div class="themes-text">{{ $item['description'] }}</div>
    @endif
    <div class="themes-data">
        @if ($reviews > 0)
            <?php echo view('components/rating', ['rating' => (float) ($item['rating'] ?? 0), 'reviews' => $reviews]); ?>
        @else
            {{ t('This theme has not been rated yet.') }}
        @endif
        @if ($version !== '')
            <div class="themes-text" title="{{ t('Version :number', $version) }}">{{ $version }}</div>
        @endif
    </div>
</div>
