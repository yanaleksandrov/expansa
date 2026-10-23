<?php
/**
 * File: the thumbnail of an image or the icon of the type, the name linking to the file, the size
 * and the dimensions below.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/parts/table/cell-file.blade.php
 *
 * @var Expansa\Builders\Table\Cell $cell
 * @var Expansa\Builders\Table\Values\File $value
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="df aic g-3">
    @if ($value->thumbnail)
        <span class="avatar avatar--rounded" style="background-image: url({{ $value->thumbnail }})"></span>
    @else
        <span class="avatar avatar--rounded bg-muted-lt t-muted"><i class="{{ $value->icon }}"></i></span>
    @endif
    <div>
        @if ($value->url)
            <a href="{{ $value->url }}" class="fw-500 t-dark" target="_blank">{{ $value->name }}</a>
        @else
            <strong>{{ $value->name }}</strong>
        @endif
        @if ($value->size || $value->width)
            <div class="fs-12 t-muted mt-1">
                {{ $value->size }}
                @if ($value->width)
                    {{ $value->size ? '·' : '' }} {{ $value->width }}×{{ $value->height }}
                @endif
            </div>
        @endif
    </div>
</div>
