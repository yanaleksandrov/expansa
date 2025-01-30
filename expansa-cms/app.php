<?php

declare(strict_types=1);

/**
 * Setting up the priority rule for translations.
 *
 * @since 2025.1
 */
\Expansa\Facades\Hook::configure(EX_PATH . 'app/Listeners');

/**
 * Register forms fields.
 *
 * @since 2025.1
 */
\Expansa\Facades\Form::configure(
    [
        'text'            => \Expansa\Builders\Forms\Fields\Input::class,
        'color'           => \Expansa\Builders\Forms\Fields\Input::class,
        'date'            => \Expansa\Builders\Forms\Fields\Input::class,
        'datetime-local'  => \Expansa\Builders\Forms\Fields\Input::class,
        'email'           => \Expansa\Builders\Forms\Fields\Input::class,
        'month'           => \Expansa\Builders\Forms\Fields\Input::class,
        'range'           => \Expansa\Builders\Forms\Fields\Input::class,
        'search'          => \Expansa\Builders\Forms\Fields\Input::class,
        'tel'             => \Expansa\Builders\Forms\Fields\Input::class,
        'time'            => \Expansa\Builders\Forms\Fields\Input::class,
        'url'             => \Expansa\Builders\Forms\Fields\Input::class,
        'week'            => \Expansa\Builders\Forms\Fields\Input::class,

        'builder'         => \Expansa\Builders\Forms\Fields\Builder::class,
        'checkbox'        => \Expansa\Builders\Forms\Fields\Checkbox::class,
        'custom'          => \Expansa\Builders\Forms\Fields\Custom::class,
        'details'         => \Expansa\Builders\Forms\Fields\Details::class,
        'divider'         => \Expansa\Builders\Forms\Fields\Divider::class,
        'file'            => \Expansa\Builders\Forms\Fields\File::class,
        'header'          => \Expansa\Builders\Forms\Fields\Header::class,
        'hidden'          => \Expansa\Builders\Forms\Fields\Hidden::class,
        'image'           => \Expansa\Builders\Forms\Fields\Image::class,
        'input'           => \Expansa\Builders\Forms\Fields\Input::class,
        'layout-group'    => \Expansa\Builders\Forms\Fields\LayoutGroup::class,
        'layout-step'     => \Expansa\Builders\Forms\Fields\LayoutStep::class,
        'layout-tab'      => \Expansa\Builders\Forms\Fields\LayoutTab::class,
        'layout-tab-menu' => \Expansa\Builders\Forms\Fields\LayoutTabMenu::class,
        'media'           => \Expansa\Builders\Forms\Fields\Media::class,
        'number'          => \Expansa\Builders\Forms\Fields\Number::class,
        'password'        => \Expansa\Builders\Forms\Fields\Password::class,
        'progress'        => \Expansa\Builders\Forms\Fields\Progress::class,
        'radio'           => \Expansa\Builders\Forms\Fields\Radio::class,
        'select'          => \Expansa\Builders\Forms\Fields\Select::class,
        'submit'          => \Expansa\Builders\Forms\Fields\Submit::class,
        'textarea'        => \Expansa\Builders\Forms\Fields\Textarea::class,
        'uploader'        => \Expansa\Builders\Forms\Fields\Uploader::class,

        'editor'          => \Expansa\Builders\Forms\Fields\Editor::class,
        'gallery'         => \Expansa\Builders\Forms\Fields\Gallery::class,
        'repeater'        => \Expansa\Builders\Forms\Fields\Repeater::class,
        'message'         => \Expansa\Builders\Forms\Fields\Message::class,
    ]
);

/**
 * Setting up the priority rule for translations.
 *
 * @since 2025.1
 */
\Expansa\Facades\I18n::configure(
    [
        EX_CORE      => EX_DASHBOARD,
        EX_DASHBOARD => EX_DASHBOARD,
        EX_PLUGINS   => EX_PLUGINS . ':dirname',
        EX_THEMES    => EX_THEMES . ':dirname',
    ],
    'i18n/%s'
);

/**
 * Add core API endpoints.
 * Important! If current request is request to API, stop code execution after Api::create().
 *
 * @since 2025.1
 */
Expansa\Api::configure('/api', sprintf('%sapp/Api', EX_PATH));

/**
 * Load installed and launch active plugins & themes.
 *
 * @since 2025.1
 */
\Expansa\Facades\Extensions::enqueue(fn () => [
    ...\Expansa\Facades\Disk::dir(EX_PLUGINS)->files('*/*.php'),
    ...\Expansa\Facades\Disk::dir(EX_THEMES)->files('*/*.php'),
]);

\Expansa\Facades\Extensions::boot('plugin');
\Expansa\Facades\Extensions::boot('theme');
