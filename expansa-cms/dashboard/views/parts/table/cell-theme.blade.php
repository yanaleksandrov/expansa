<?php
/**
 * Card of a theme: screenshot with the actions, title, description, rating and version.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-theme.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\Extension $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="themes-item">
    <div class="themes-image" style="background-image: url({{ $value->screenshot }})">
        <div class="themes-action">
            <button class="btn btn--outline" type="button">{{ t('View Demo') }}</button>
            @if (! $value->installed)
                <button class="btn btn--outline" type="button">{{ t('Activate') }}</button>
            @else
                <button class="btn btn--primary" type="button">{{ t('Customize') }}</button>
            @endif
        </div>
    </div>
    <h6 class="themes-title">{{ $value->title }}</h6>
    @if ($value->description)
        <div class="themes-text">{{ $value->description }}</div>
    @endif
    <div class="themes-data">
        @if ($value->reviews > 0)
            <?php
            echo view('parts/rating', [
                'rating'  => $value->rating,
                'reviews' => $value->reviews
            ]);
            ?>
        @else
            {{ t('This theme has not been rated yet.') }}
        @endif
        @if ($value->version)
            <div class="themes-text" title="{{ t('Version :number', $value->version) }}">{{ $value->version }}</div>
        @endif
    </div>
</div>
