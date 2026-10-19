<?php

/**
 * Design system: the form fields of the dashboard with their classes, states and sizes.
 *
 * This template can be overridden by copying it to themes/yourtheme/toolkit/templates/design-system.php
 *
 * @package Expansa\Templates
 */

declare(strict_types=1);

use Expansa\Builders\Form;

defined('EX_PATH') || exit;

$sizeFields = [];
foreach (['xs', 'sm', '', 'lg', 'xl'] as $size) {
    $class = $size ? "field field--$size" : 'field';

    $sizeFields[] = [
        'caption' => $class,
        'fields'  => [
            [
                'type'       => 'text',
                'name'       => 'ds_size_' . ($size ?: 'md'),
                'label'      => $size ?: 'md',
                'class'      => $class,
                'attributes' => ['placeholder' => t('Your name')],
            ],
        ],
    ];
}

// a variant switches the classes of every field and the surface under them; `data-class` keeps the own ones
$variantSwitch = Form::renderFields([
    [
        'type'       => 'button-group',
        'name'       => 'ds_variant',
        'label'      => t('Fields'),
        'class'      => 'field fdcr mb-6',
        'attributes' => [
            'value'   => 'default',
            '@change' => implode(' ', [
                'variant = $event.target.value;',
                "\$root.querySelectorAll('.design-system__main .field').forEach(field => {",
                'field.dataset.class ??= field.className;',
                "field.className = field.dataset.class.replace(/\s*field--(raised|plain)/g, '')",
                "+ (variant === 'default' ? '' : ' field--' + variant);",
                '})',
            ]),
        ],
        'options'    => [
            'default' => t('Default'),
            'raised'  => t('Raised'),
            'plain'   => t('Plain'),
        ],
    ],
]);

$badgeColors = ['dark', 'muted', 'white', 'red', 'amber', 'orange', 'green', 'blue', 'purple'];

// badges of each color with the given class suffix, e.g. `-lt`
$badges = static fn (string $suffix): string => implode(' ', array_map(
    fn (string $color): string => sprintf('<span class="badge badge--%s%s">%s</span>', $color, $suffix, ucfirst($color)),
    $badgeColors,
));

$buttonVariants = [];
foreach (['btn--primary', 'btn--secondary', 'btn--success', 'btn--danger', 'btn--outline', ''] as $variant) {
    $buttonVariants[] = [
        'content' => ucfirst(str_replace('btn--', '', $variant) ?: 'default'),
        'class'   => trim("btn $variant"),
    ];
}

$buttonSizes = [];
foreach (['xs', 'sm', '', 'lg', 'xl'] as $size) {
    $buttonSizes[] = [
        'content' => $size ?: 'md',
        'class'   => 'btn btn--primary' . ($size ? " btn--$size" : ''),
    ];
}

// a badge of the same size step as its button
$badgeButtons = [];
foreach (['xs', 'sm', '', 'lg', 'xl'] as $size) {
    $badgeButtons[] = [
        'content' => sprintf(
            '%s <span class="badge badge--round badge--red%s">8</span>',
            t('Inbox'),
            $size ? " badge--$size" : '',
        ),
        'class'   => 'btn btn--outline' . ($size ? " btn--$size" : ''),
    ];
}

// a single and a multiple select of each size
$selectSizes = [];
foreach (['xs', 'sm', '', 'lg', 'xl'] as $size) {
    $class = $size ? "field field--$size" : 'field';

    $selectSizes[] = [
        'caption' => $class,
        'fields'  => [
            [
                'type'    => 'select',
                'name'    => 'ds_select_size_' . ($size ?: 'md'),
                'label'   => $size ?: 'md',
                'class'   => $class,
                'options' => [
                    'one' => t('One'),
                    'two' => t('Two'),
                ],
            ],
            [
                'type'       => 'select',
                'name'       => 'ds_select_size_multiple_' . ($size ?: 'md'),
                'class'      => $class,
                'attributes' => ['multiple' => true, 'value' => 'html'],
                'options'    => [
                    'html' => 'HTML',
                    'css'  => 'CSS',
                    'php'  => 'PHP',
                ],
            ],
        ],
    ];
}

// a button group of each size beside a button of the same size, to compare their heights
$groupSizes = [];
foreach (['xs', 'sm', '', 'lg', 'xl'] as $size) {
    $class = $size ? "field field--$size" : 'field';

    $groupSizes[] = [
        'caption' => $class,
        'fields'  => [
            [
                'type'       => 'button-group',
                'name'       => 'ds_group_size_' . ($size ?: 'md'),
                'label'      => $size ?: 'md',
                'class'      => $class,
                'attributes' => ['value' => 'day'],
                'options'    => [
                    'day'   => t('Day'),
                    'week'  => t('Week'),
                    'month' => t('Month'),
                ],
            ],
            [
                'type'    => 'buttons',
                'name'    => 'ds_group_size_button_' . ($size ?: 'md'),
                'options' => [
                    [
                        'content' => t('Button'),
                        'class'   => 'btn btn--outline' . ($size ? " btn--$size" : ''),
                    ],
                ],
            ],
        ],
    ];
}

$states = ['Alabama', 'Alaska', 'Arizona', 'California', 'Colorado', 'Florida', 'Georgia', 'Texas'];
$states = array_combine(array_map('strtolower', $states), $states);

// a section is a card of examples; an example is a caption with the fields or the markup of a state
$sections = [
    [
        'id'          => 'states',
        'title'       => t('Basic states'),
        'description' => t('Text inputs: value, placeholder, password, disabled, readonly and required.'),
        'examples'    => [
            [
                'caption' => 'value',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_value',
                        'label'      => t('Input value'),
                        'attributes' => ['value' => 'Input value'],
                    ],
                ],
            ],
            [
                'caption' => 'placeholder',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_text',
                        'label'      => t('Text'),
                        'attributes' => ['placeholder' => t('Input placeholder')],
                    ],
                ],
            ],
            [
                'caption' => 'type: password',
                'fields'  => [
                    [
                        'type'       => 'password',
                        'name'       => 'ds_password',
                        'label'      => t('Password'),
                        'attributes' => ['placeholder' => t('Your password')],
                    ],
                ],
            ],
            [
                'caption' => 'disabled',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_disabled',
                        'label'      => t('Disabled'),
                        'attributes' => ['value' => 'Well, she turned me into a newt.', 'disabled' => true],
                    ],
                ],
            ],
            [
                'caption' => 'readonly',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_readonly',
                        'label'      => t('Readonly'),
                        'attributes' => ['value' => 'Well, how\'d you become king, then?', 'readonly' => true],
                    ],
                ],
            ],
            [
                'caption' => 'required',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_required',
                        'label'      => t('Required'),
                        'attributes' => ['placeholder' => t('Required...'), 'required' => true],
                    ],
                ],
            ],
            [
                'caption' => 'instruction',
                'fields'  => [
                    [
                        'type'        => 'email',
                        'name'        => 'ds_instruction',
                        'label'       => t('Email address'),
                        'instruction' => t('We\'ll never share your email with anyone else.'),
                        'attributes'  => ['placeholder' => 'name@example.com'],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'sizes',
        'title'       => t('Sizes'),
        'description' => t('The height and the font of a field come from the size modifier of the wrapper.'),
        'examples'    => $sizeFields,
    ],
    [
        'id'          => 'addons',
        'title'       => t('Input addons'),
        'description' => t('Prefixes, suffixes, icons and actions: the before, after, copy and tooltip keys.'),
        'examples'    => [
            [
                'caption' => 'before',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_prefix',
                        'label'      => t('Prefix'),
                        'before'     => '@',
                        'attributes' => ['placeholder' => t('username')],
                    ],
                ],
            ],
            [
                'caption' => 'after',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_suffix',
                        'label'      => t('Suffix'),
                        'after'      => '.expansa.io',
                        'attributes' => ['placeholder' => t('subdomain')],
                    ],
                ],
            ],
            [
                'caption' => 'before + after',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_prefix_suffix',
                        'label'      => t('Prefix and suffix'),
                        'before'     => '<samp class="t-muted">https://</samp>',
                        'after'      => '<samp class="t-muted">.expansa.io</samp>',
                        'attributes' => ['placeholder' => t('subdomain')],
                    ],
                ],
            ],
            [
                'caption' => 'before: icon',
                'fields'  => [
                    [
                        'type'       => 'search',
                        'name'       => 'ds_icon',
                        'label'      => t('Icon input'),
                        'before'     => '<i class="ph ph-magnifying-glass t-muted"></i>',
                        'attributes' => ['placeholder' => t('Search...')],
                    ],
                ],
            ],
            [
                'caption' => 'after: kbd',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_kbd',
                        'label'      => t('Shortcut'),
                        'after'      => '<code>ctrl + K</code>',
                        'attributes' => ['placeholder' => t('Search for...')],
                    ],
                ],
            ],
            [
                'caption' => 'class: is-loading',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_loader',
                        'label'      => t('Loader input'),
                        'attributes' => ['class' => 'is-loading', 'placeholder' => t('Search for...')],
                    ],
                ],
            ],
            [
                'caption' => 'tooltip',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_tooltip',
                        'label'      => t('Input with help icon'),
                        'tooltip'    => t('We\'ll never share your data with anyone else'),
                        'attributes' => ['placeholder' => t('Search for...')],
                    ],
                ],
            ],
            [
                'caption' => 'copy + readonly',
                'fields'  => [
                    [
                        'type'        => 'text',
                        'name'        => 'ds_api_key',
                        'label'       => t('API key'),
                        'copy'        => true,
                        'instruction' => t('Use this key to authenticate requests to the API.'),
                        'attributes'  => ['value' => 'ex_live_4eC39HqLyjWDarjtT1zdp7dc', 'readonly' => true],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'validation',
        'title'       => t('Validation states'),
        'description' => t('The invalid key adds is-invalid and field-error, as youla-ajax.js does.'),
        'examples'    => [
            [
                'caption' => 'invalid',
                'fields'  => [
                    [
                        'type'       => 'email',
                        'name'       => 'ds_invalid',
                        'label'      => t('Invalid input'),
                        'invalid'    => t('Invalid feedback'),
                        'attributes' => ['value' => 'name@'],
                    ],
                ],
            ],
            [
                'caption' => 'field--raised + invalid',
                'fields'  => [
                    [
                        'type'       => 'text',
                        'name'       => 'ds_invalid_raised',
                        'label'      => t('Full name'),
                        'class'      => 'field field--raised',
                        'before'     => '<i class="ph ph-user t-muted"></i>',
                        'invalid'    => t('The full name is required.'),
                        'attributes' => ['placeholder' => t('Your name'), 'required' => true],
                    ],
                ],
            ],
            [
                'caption' => 'textarea + invalid',
                'fields'  => [
                    [
                        'type'       => 'textarea',
                        'name'       => 'ds_invalid_textarea',
                        'label'      => t('Message'),
                        'invalid'    => t('The message must be at least 20 characters.'),
                        'attributes' => ['value' => 'Hello'],
                    ],
                ],
            ],
            [
                'caption' => 'select + invalid',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_invalid_select',
                        'label'   => t('Country'),
                        'invalid' => t('Choose a country.'),
                        'options' => [
                            ''   => t('Select...'),
                            'de' => t('Germany'),
                            'pl' => t('Poland'),
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'textarea',
        'title'       => t('Textarea'),
        'description' => t('The height follows the content, rows caps it: field-sizing, no JS.'),
        'examples'    => [
            [
                'caption' => 'no rows',
                'fields'  => [
                    [
                        'type'        => 'textarea',
                        'name'        => 'ds_textarea',
                        'label'       => t('Autosize textarea'),
                        'instruction' => t('The textarea grows while you type, without a limit.'),
                        'attributes'  => ['placeholder' => t('Type something...')],
                    ],
                ],
            ],
            [
                'caption' => 'rows: 4, grows up to 4 lines',
                'fields'  => [
                    [
                        'type'       => 'textarea',
                        'name'       => 'ds_textarea_rows',
                        'label'      => t('Bio'),
                        'attributes' => [
                            'rows'  => 4,
                            'value' => 'Oh! Come and see the violence inherent in the system! Help, help, I\'m being repressed!',
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'disabled',
                'fields'  => [
                    [
                        'type'       => 'textarea',
                        'name'       => 'ds_textarea_disabled',
                        'label'      => t('Disabled'),
                        'attributes' => ['rows' => 2, 'value' => t('Content'), 'disabled' => true],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'select',
        'title'       => t('Select'),
        'description' => t('u-select turns a select into a custom one: search, multiple, groups, icons and descriptions.'),
        'examples'    => [
            [
                'caption' => 'select',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select',
                        'label'   => t('Select'),
                        'options' => [
                            'one'   => t('One'),
                            'two'   => t('Two'),
                            'three' => t('Three'),
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'u-select: false',
                'fields'  => [
                    [
                        'type'       => 'select',
                        'name'       => 'ds_select_native',
                        'label'      => t('Native select'),
                        'attributes' => ['u-select' => false],
                        'options'    => [
                            'one'   => t('One'),
                            'two'   => t('Two'),
                            'three' => t('Three'),
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'u-select: false, multiple',
                'fields'  => [
                    [
                        'type'       => 'select',
                        'name'       => 'ds_select_native_multiple',
                        'label'      => t('Native select multiple'),
                        'attributes' => [
                            'u-select' => false,
                            'multiple' => true,
                        ],
                        'options'    => [
                            'html' => 'HTML',
                            'css'  => 'CSS',
                            'php'  => 'PHP',
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'before: icon',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select_before',
                        'label'   => t('Select with an icon before'),
                        'before'  => '<i class="ph ph-globe t-muted"></i>',
                        'options' => [
                            'en' => t('English'),
                            'ru' => t('Russian'),
                            'de' => t('German'),
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'after: icon',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select_after',
                        'label'   => t('Select with an icon after'),
                        'after'   => '<i class="ph ph-info t-muted"></i>',
                        'options' => [
                            'one'   => t('One'),
                            'two'   => t('Two'),
                            'three' => t('Three'),
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'before & after: text',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select_url',
                        'label'   => t('Select with text around'),
                        'before'  => '<samp class="t-muted">https://</samp>',
                        'after'   => '<samp class="t-muted">.expansa.io</samp>',
                        'options' => [
                            'www'  => 'www',
                            'shop' => 'shop',
                            'docs' => 'docs',
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'u-select: showSearch',
                'fields'  => [
                    [
                        'type'       => 'select',
                        'name'       => 'ds_select_search',
                        'label'      => t('Select with search'),
                        'attributes' => ['u-select' => '{"showSearch": 1}', 'value' => 'texas'],
                        'options'    => $states,
                    ],
                ],
            ],
            [
                'caption' => 'multiple',
                'fields'  => [
                    [
                        'type'       => 'select',
                        'name'       => 'ds_select_multiple',
                        'label'      => t('Select multiple'),
                        'attributes' => ['multiple' => true],
                        'options'    => $states,
                    ],
                ],
            ],
            [
                'caption' => 'options: optgroup',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select_group',
                        'label'   => t('Select with optgroup'),
                        'options' => [
                            'frontend' => [
                                'label'   => t('Frontend'),
                                'options' => [
                                    'html' => 'HTML',
                                    'css'  => 'CSS',
                                    'js'   => 'JavaScript',
                                ],
                            ],
                            'backend'  => [
                                'label'   => t('Backend'),
                                'options' => [
                                    'php'    => 'PHP',
                                    'python' => 'Python',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'options: icon',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select_icons',
                        'label'   => t('Select with icons'),
                        'options' => [
                            'copy'  => [
                                'content' => t('Copy'),
                                'icon'    => 'ph ph-copy',
                            ],
                            'paste' => [
                                'content' => t('Paste'),
                                'icon'    => 'ph ph-clipboard',
                            ],
                            'cut'   => [
                                'content' => t('Cut'),
                                'icon'    => 'ph ph-scissors',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'options: description',
                'fields'  => [
                    [
                        'type'    => 'select',
                        'name'    => 'ds_select_priority',
                        'label'   => t('Select with priority'),
                        'options' => [
                            'urgent' => [
                                'content'     => t('Urgent'),
                                'icon'        => 'ph ph-warning t-red',
                                'description' => t('Fix right now'),
                            ],
                            'normal' => [
                                'content'     => t('Normal'),
                                'icon'        => 'ph ph-circle t-orange',
                                'description' => t('Fix this week'),
                            ],
                            'low'    => [
                                'content'     => t('Low'),
                                'icon'        => 'ph ph-circle t-green',
                                'description' => t('Fix some day'),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'disabled',
                'fields'  => [
                    [
                        'type'       => 'select',
                        'name'       => 'ds_select_disabled',
                        'label'      => t('Disabled'),
                        'attributes' => ['disabled' => true],
                        'options'    => ['one' => t('One')],
                    ],
                ],
            ],
            [
                'caption' => 'reset',
                'fields'  => [
                    [
                        'type'        => 'select',
                        'name'        => 'ds_select_reset',
                        'label'       => t('Select with reset'),
                        'reset'       => true,
                        'instruction' => t('Change the value to see the reset link.'),
                        'attributes'  => ['value' => 'one'],
                        'options'     => [
                            'one' => t('One'),
                            'two' => t('Two'),
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'select-sizes',
        'title'       => t('Select sizes'),
        'description' => t('A single and a multiple select with the size modifiers of the field.'),
        'examples'    => $selectSizes,
    ],
    [
        'id'          => 'pickers',
        'title'       => t('Pickers'),
        'description' => t('Number, date, time, color, range and tel inputs with their youla.js widgets.'),
        'examples'    => [
            [
                'caption' => 'type: number + after',
                'fields'  => [
                    [
                        'type'       => 'number',
                        'name'       => 'ds_number',
                        'label'      => t('Number'),
                        'after'      => '<samp class="t-muted">px</samp>',
                        'attributes' => ['value' => 5, 'min' => 0, 'max' => 10],
                    ],
                ],
            ],
            [
                'caption' => 'type: date',
                'fields'  => [
                    [
                        'type'       => 'date',
                        'name'       => 'ds_date',
                        'label'      => t('Datepicker'),
                        'attributes' => ['placeholder' => t('Select a date')],
                    ],
                ],
            ],
            [
                'caption' => 'type: datetime-local',
                'fields'  => [
                    [
                        'type'       => 'datetime-local',
                        'name'       => 'ds_datetime',
                        'label'      => t('Date and time'),
                        'attributes' => ['placeholder' => t('Select a date and time')],
                    ],
                ],
            ],
            [
                'caption' => 'type: time',
                'fields'  => [
                    [
                        'type'       => 'time',
                        'name'       => 'ds_time',
                        'label'      => t('Time'),
                        'attributes' => ['placeholder' => t('Select a time')],
                    ],
                ],
            ],
            [
                'caption' => 'type: month',
                'fields'  => [
                    [
                        'type'       => 'month',
                        'name'       => 'ds_month',
                        'label'      => t('Month'),
                        'attributes' => ['placeholder' => t('Select a month')],
                    ],
                ],
            ],
            [
                'caption' => 'type: week, native',
                'fields'  => [
                    [
                        'type'  => 'week',
                        'name'  => 'ds_week',
                        'label' => t('Week'),
                    ],
                ],
            ],
            [
                'caption' => 'type: color, u-filler',
                'fields'  => [
                    [
                        'type'       => 'color',
                        'name'       => 'ds_color',
                        'label'      => t('Color picker'),
                        'attributes' => ['type' => 'text', 'u-filler' => '', 'value' => '#005cf4'],
                    ],
                ],
            ],
            [
                'caption' => 'type: range',
                'fields'  => [
                    [
                        'type'       => 'range',
                        'name'       => 'ds_range_native',
                        'label'      => t('Native range input'),
                        'attributes' => ['value' => 40, 'min' => 0, 'max' => 100],
                    ],
                ],
            ],
            [
                'caption' => 'type: range, u-ranger',
                'fields'  => [
                    [
                        'type'       => 'range',
                        'name'       => 'ds_range',
                        'label'      => t('Range input'),
                        'attributes' => ['u-ranger' => '', 'value' => 40, 'min' => 0, 'max' => 100],
                    ],
                ],
            ],
            [
                'caption' => 'type: tel',
                'fields'  => [
                    [
                        'type'       => 'tel',
                        'name'       => 'ds_tel',
                        'label'      => t('Telephone'),
                        'before'     => '<i class="ph ph-phone t-muted"></i>',
                        'attributes' => ['placeholder' => '+1 (000) 000-00-00'],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'password',
        'title'       => t('Password'),
        'description' => t('Visibility switcher, generator, strength indicator and character requirements.'),
        'examples'    => [
            [
                'caption' => 'before + switcher + copy',
                'fields'  => [
                    [
                        'type'       => 'password',
                        'name'       => 'ds_password_copy',
                        'label'      => t('Password'),
                        'before'     => '<i class="ph ph-lock-key t-muted"></i>',
                        'copy'       => true,
                        'attributes' => ['value' => 'Expansa2026'],
                    ],
                ],
            ],
            [
                'caption' => 'generator + indicator + characters',
                'fields'  => [
                    [
                        'type'        => 'password',
                        'name'        => 'ds_password_strength',
                        'label'       => t('Password strength'),
                        'generator'   => true,
                        'indicator'   => true,
                        'instruction' => t('It is a hint, not a validation.'),
                        'characters'  => [
                            'lowercase' => 2,
                            'uppercase' => 2,
                            'special'   => 1,
                            'length'    => 12,
                            'digit'     => 2,
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'checkboxes',
        'title'       => t('Checkboxes'),
        'description' => t('A single checkbox or options, each with an icon and a description.'),
        'examples'    => [
            [
                'caption' => 'checkbox',
                'fields'  => [
                    [
                        'type'  => 'checkbox',
                        'name'  => 'ds_checkbox',
                        'label' => t('Checkbox input'),
                    ],
                    [
                        'type'       => 'checkbox',
                        'name'       => 'ds_checkbox_checked',
                        'label'      => t('Checked checkbox input'),
                        'attributes' => ['checked' => true],
                    ],
                    [
                        'type'       => 'checkbox',
                        'name'       => 'ds_checkbox_disabled',
                        'label'      => t('Disabled checkbox input'),
                        'attributes' => ['disabled' => true],
                    ],
                    [
                        'type'       => 'checkbox',
                        'name'       => 'ds_checkbox_disabled_checked',
                        'label'      => t('Checked disabled checkbox input'),
                        'attributes' => ['disabled' => true, 'checked' => true],
                    ],
                ],
            ],
            [
                'caption' => 'options: description',
                'fields'  => [
                    [
                        'type'    => 'checkbox',
                        'name'    => 'ds_checkboxes',
                        'label'   => t('Checkboxes with description'),
                        'options' => [
                            'ds_checkbox_default'  => [
                                'content'     => t('Default checkbox'),
                                'description' => t('Some text that describes the option.'),
                                'checked'     => true,
                            ],
                            'ds_checkbox_long'     => [
                                'content'     => t('Longer checkbox item that wraps on to two separate lines'),
                                'description' => t('Another text that describes the option.'),
                            ],
                            'ds_checkbox_no_descr' => [
                                'content' => t('Default checkbox without description'),
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'options: icon',
                'fields'  => [
                    [
                        'type'    => 'checkbox',
                        'name'    => 'ds_checkbox_icons',
                        'label'   => t('Checkboxes with icons'),
                        'options' => [
                            'ds_checkbox_html' => [
                                'content'     => 'HTML',
                                'icon'        => 'ph ph-file-html',
                                'description' => t('Markup of the pages.'),
                            ],
                            'ds_checkbox_css'  => [
                                'content'     => 'CSS',
                                'icon'        => 'ph ph-file-css',
                                'description' => t('Styles of the pages.'),
                                'checked'     => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'switches',
        'title'       => t('Toggle switches'),
        'description' => t('field--ui turns checkboxes into switches in a joined list.'),
        'examples'    => [
            [
                'caption' => 'field field--ui',
                'fields'  => [
                    [
                        'type'    => 'checkbox',
                        'name'    => 'ds_switches',
                        'label'   => t('Notification'),
                        'class'   => 'field field--ui',
                        'options' => [
                            'ds_switch_push'  => [
                                'content'     => t('Push notifications'),
                                'description' => t('Receive alerts on your device.'),
                                'icon'        => 'ph ph-bell',
                                'checked'     => true,
                            ],
                            'ds_switch_sms'   => [
                                'content'     => t('SMS notifications'),
                                'description' => t('Receive alerts by text messages.'),
                                'icon'        => 'ph ph-chat-text',
                            ],
                            'ds_switch_email' => [
                                'content'     => t('Email notifications'),
                                'description' => t('Receive a daily digest by email.'),
                                'icon'        => 'ph ph-envelope',
                                'checked'     => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'field field--ui + disabled',
                'fields'  => [
                    [
                        'type'        => 'checkbox',
                        'name'        => 'ds_switch_single',
                        'label'       => t('I agree with terms and conditions'),
                        'instruction' => t('A single switch without options.'),
                        'class'       => 'field field--ui',
                    ],
                    [
                        'type'       => 'checkbox',
                        'name'       => 'ds_switch_disabled',
                        'label'      => t('Disabled switch'),
                        'class'      => 'field field--ui',
                        'attributes' => ['disabled' => true],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'radios',
        'title'       => t('Radios'),
        'description' => t('Options of one name: plain, with descriptions, as a switch list or as selectable cards.'),
        'examples'    => [
            [
                'caption' => 'radio',
                'fields'  => [
                    [
                        'type'    => 'radio',
                        'name'    => 'ds_radio',
                        'label'   => t('Radios'),
                        'options' => [
                            'one'   => [
                                'content' => t('Option 1'),
                                'checked' => true,
                            ],
                            'two'   => ['content' => t('Option 2')],
                            'three' => ['content' => t('Option 3')],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'options: icon + description',
                'fields'  => [
                    [
                        'type'    => 'radio',
                        'name'    => 'ds_radio_payment',
                        'label'   => t('Payment method'),
                        'options' => [
                            'visa'       => [
                                'content'     => t('Visa ending in 7998'),
                                'description' => t('Expires 12/2028'),
                                'icon'        => 'ph ph-credit-card',
                                'checked'     => true,
                            ],
                            'mastercard' => [
                                'content'     => t('Mastercard ending in 1000'),
                                'description' => t('Expires 04/2027'),
                                'icon'        => 'ph ph-credit-card',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'field field--ui',
                'fields'  => [
                    [
                        'type'    => 'radio',
                        'name'    => 'ds_radio_ui',
                        'label'   => t('Theme'),
                        'class'   => 'field field--ui',
                        'options' => [
                            'light'  => [
                                'content'     => t('Light'),
                                'description' => t('Always use the light theme.'),
                                'icon'        => 'ph ph-sun',
                                'checked'     => true,
                            ],
                            'dark'   => [
                                'content'     => t('Dark'),
                                'description' => t('Always use the dark theme.'),
                                'icon'        => 'ph ph-moon',
                            ],
                            'system' => [
                                'content'     => t('System'),
                                'description' => t('Follow the system settings.'),
                                'icon'        => 'ph ph-desktop',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'field field--button',
                'fields'  => [
                    [
                        'type'    => 'radio',
                        'name'    => 'ds_radio_button',
                        'label'   => t('Selectgroup'),
                        'class'   => 'field field--button',
                        'options' => [
                            'html' => [
                                'content' => 'HTML',
                                'checked' => true,
                            ],
                            'css'  => ['content' => 'CSS'],
                            'php'  => ['content' => 'PHP'],
                            'js'   => ['content' => 'JavaScript'],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'field field--button + icon',
                'fields'  => [
                    [
                        'type'    => 'radio',
                        'name'    => 'ds_radio_button_icons',
                        'label'   => t('Selectgroup with icons and text'),
                        'class'   => 'field field--button',
                        'options' => [
                            'home'   => [
                                'content'     => t('Home'),
                                'description' => t('For personal use'),
                                'icon'        => 'ph ph-house',
                                'checked'     => true,
                            ],
                            'office' => [
                                'content'     => t('Office'),
                                'description' => t('For a team'),
                                'icon'        => 'ph ph-buildings',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'disabled',
                'fields'  => [
                    [
                        'type'       => 'radio',
                        'name'       => 'ds_radio_disabled',
                        'label'      => t('Disabled radio'),
                        'attributes' => ['disabled' => true],
                        'options'    => [
                            'one' => [
                                'content' => t('Checked disabled'),
                                'checked' => true,
                            ],
                            'two' => ['content' => t('Disabled')],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'button-group',
        'title'       => t('Button group'),
        'description' => t('Radios as a segmented control: field-buttons beside the label.'),
        'examples'    => [
            [
                'caption' => 'type: button-group',
                'fields'  => [
                    [
                        'type'        => 'button-group',
                        'name'        => 'ds_button_group',
                        'label'       => t('Interval'),
                        'instruction' => t('How often the data refreshes.'),
                        'attributes'  => ['value' => '5'],
                        'options'     => [
                            '1'  => t('1 min'),
                            '5'  => t('5 min'),
                            '10' => t('10 min'),
                            '30' => t('30 min'),
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'type: button-group',
                'fields'  => [
                    [
                        'type'       => 'button-group',
                        'name'       => 'ds_button_group_method',
                        'label'      => t('Method'),
                        'tooltip'    => t('HTTP method of the request'),
                        'attributes' => ['value' => 'get'],
                        'options'    => [
                            'get'    => 'GET',
                            'post'   => 'POST',
                            'put'    => 'PUT',
                            'delete' => 'DELETE',
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'button-group-sizes',
        'title'       => t('Button group sizes'),
        'description' => t('The size modifiers of the field, a button of the same size below for the height.'),
        'examples'    => $groupSizes,
    ],
    [
        'id'          => 'buttons',
        'title'       => t('Buttons'),
        'description' => t('Colors, sizes and modifiers of btn.'),
        'examples'    => [
            [
                'caption' => 'btn--primary, --secondary, --success, --danger, --outline',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons',
                        'label'   => t('Colors'),
                        'options' => $buttonVariants,
                    ],
                ],
            ],
            [
                'caption' => 'btn--xs, --sm, --lg, --xl',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_sizes',
                        'label'   => t('Sizes'),
                        'options' => $buttonSizes,
                    ],
                ],
            ],
            [
                'caption' => 'btn--icon, --rounded, is-loading, disabled',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_modifiers',
                        'label'   => t('Modifiers'),
                        'options' => [
                            [
                                'icon'  => 'ph ph-gear',
                                'class' => 'btn btn--outline btn--icon',
                            ],
                            [
                                'content' => t('Rounded'),
                                'class'   => 'btn btn--primary btn--rounded',
                            ],
                            [
                                'content' => t('Loading'),
                                'class'   => 'btn btn--primary is-loading',
                            ],
                            [
                                'content'    => t('Disabled'),
                                'class'      => 'btn btn--primary',
                                'attributes' => ['disabled' => true],
                            ],
                            [
                                'content'    => t('Animated'),
                                'class'      => 'btn btn--outline btn--animated',
                                'attributes' => ['data-txt' => t('Hello!')],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'is-loading + badge, icon',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_loading',
                        'label'   => t('Loading'),
                        'options' => [
                            [
                                'content' => t('Inbox') . ' <span class="badge badge--round badge--red">12</span>',
                                'class'   => 'btn btn--outline is-loading',
                            ],
                            [
                                'content' => t('Save'),
                                'icon'    => 'ph ph-floppy-disk',
                                'class'   => 'btn btn--primary is-loading',
                            ],
                            [
                                'content' => sprintf(
                                    '%s <span class="badge badge--white"><i class="ph ph-check"></i> v2.4</span>',
                                    t('Deploy'),
                                ),
                                'icon'    => 'ph ph-rocket-launch',
                                'class'   => 'btn btn--success is-loading',
                            ],
                            [
                                'icon'  => 'ph ph-gear',
                                'class' => 'btn btn--outline btn--icon is-loading',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'btn + badge: counters',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_counters',
                        'label'   => t('Counters'),
                        'options' => [
                            [
                                'content' => t('Inbox') . ' <span class="badge badge--round badge--red">12</span>',
                                'class'   => 'btn btn--outline',
                            ],
                            [
                                'content' => t('Comments') . ' <span class="badge badge--round">3</span>',
                                'class'   => 'btn',
                            ],
                            [
                                'content' => t('Messages') . ' <span class="badge badge--blue">4</span>',
                                'class'   => 'btn btn--primary',
                            ],
                            [
                                'content' => t('Tasks') . ' <span class="badge badge--dark">27</span>',
                                'class'   => 'btn btn--secondary',
                            ],
                            [
                                'content' => t('Errors') . ' <span class="badge badge--red">99+</span>',
                                'class'   => 'btn btn--danger',
                            ],
	                        [
		                        'content' => sprintf(
			                        '%s <span class="badge badge--green"><i class="ph ph-check"></i> v2.4</span>',
			                        t('Deploy'),
		                        ),
		                        'class'   => 'btn btn--success',
	                        ],
	                        [
		                        'content' => sprintf(
			                        '%s <span class="badge badge--orange">v2026.4</span>',
			                        t('Deploy'),
		                        ),
		                        'class'   => 'btn btn--warning',
	                        ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'btn + badge: status, dot, icon',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_statuses',
                        'label'   => t('Statuses'),
                        'options' => [
                            [
                                'content' => t('Upgrade') . ' <span class="badge badge--purple-lt">Pro</span>',
                                'class'   => 'btn btn--outline',
                            ],
                            [
                                'content' => sprintf(
                                    '%s <span class="badge badge--orange-lt">%s</span>',
                                    t('Reports'),
                                    t('Beta'),
                                ),
                                'class'   => 'btn btn--outline',
                            ],
                            [
                                'content' => '<span class="badge badge--green"></span> ' . t('Online'),
                                'class'   => 'btn btn--outline',
                            ],
                            [
                                'content' => t('Updates') . ' <span class="badge badge--red"></span>',
                                'icon'    => 'ph ph-bell',
                                'class'   => 'btn btn--outline',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'caption' => 'btn--{size} + badge--{size}',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_badge_sizes',
                        'label'   => t('Badge sizes'),
                        'options' => $badgeButtons,
                    ],
                ],
            ],
            [
                'caption' => 'icon + btn--full',
                'fields'  => [
                    [
                        'type'    => 'buttons',
                        'name'    => 'ds_buttons_full',
                        'label'   => t('Form buttons'),
                        'options' => [
                            [
                                'content' => t('Login with GitHub'),
                                'icon'    => 'ph ph-github-logo',
                                'class'   => 'btn btn--secondary btn--lg btn--full',
                            ],
                            [
                                'content' => t('Login with X'),
                                'icon'    => 'ph ph-x-logo',
                                'class'   => 'btn btn--outline btn--lg btn--full',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    [
        'id'          => 'badges',
        'title'       => t('Badges'),
        'description' => t('Colors of constants, light tints, sizes and shapes of badge.'),
        'examples'    => [
            [
                'caption' => 'badge--{color}',
                'html'    => '<div class="df fww g-2">' . $badges('') . '</div>',
            ],
            [
                'caption' => 'badge--{color}-lt',
                'html'    => '<div class="df fww g-2">' . $badges('-lt') . '</div>',
            ],
            [
                'caption' => 'badge--xs, --sm, --lg, --xl',
                'html'    => sprintf(
                    '<div class="df fww aic g-2">%s</div>',
                    implode(' ', array_map(
                        fn (string $size): string => sprintf(
                            '<span class="badge badge--blue-lt%s">%s</span>',
                            $size ? " badge--$size" : '',
                            $size ?: 'md',
                        ),
                        ['xs', 'sm', '', 'lg', 'xl'],
                    )),
                ),
            ],
            [
                'caption' => 'badge--round, --icon, :empty, is-loading',
                'html'    => sprintf(
                    '<div class="df fww aic g-2">%s</div>',
                    implode(' ', [
                        '<span class="badge badge--green-lt badge--round">' . t('Active') . '</span>',
                        '<span class="badge badge--red badge--round">12</span>',
                        '<span class="badge badge--icon"><i class="ph ph-bell"></i></span>',
                        '<span class="badge badge--purple-lt"><i class="ph ph-star"></i> ' . t('Featured') . '</span>',
                        '<span class="badge badge--green"></span>',
                        '<span class="badge badge--orange"></span>',
                        '<span class="badge is-loading">' . t('Loading') . '</span>',
                    ]),
                ),
            ],
        ],
    ],
    [
        'id'          => 'misc',
        'title'       => t('Progress and divider'),
        'description' => t('Progress bar with u-progress and the card-hr divider.'),
        'examples'    => [
            [
                'caption' => 'type: progress',
                'fields'  => [
                    [
                        'type'        => 'progress',
                        'name'        => 'ds_progress',
                        'label'       => t('Uploading'),
                        'instruction' => t('38% complete'),
                        'max'         => 100,
                        'value'       => 38,
                    ],
                ],
            ],
            [
                'caption' => 'type: divider',
                'fields'  => [
                    [
                        'type'  => 'divider',
                        'name'  => 'ds_divider',
                        'label' => t('Or'),
                    ],
                ],
            ],
        ],
    ],
];
?>
<div class="design-system" u-data="{submitted: '', variant: 'default'}" :data-variant="variant">
    <aside class="design-system__nav">
        <h4><?php echo t('Design system'); ?></h4>
        <div class="t-muted fs-12 mb-4"><?php echo t('Form fields with their classes, states and sizes'); ?></div>
        <?php echo $variantSwitch; ?>
        <?php foreach ($sections as $section) : ?>
            <a class="design-system__link" href="#<?php echo $section['id']; ?>"><?php echo $section['title']; ?></a>
        <?php endforeach; ?>
    </aside>
    <form
        class="design-system__main"
        id="design-system"
        method="POST"
        @submit.prevent="submitted = JSON.stringify([...new FormData($el)], null, 2)"
        @reset="submitted = ''"
    >
        <?php foreach ($sections as $section) : ?>
            <section class="design-system__section card card-border" id="<?php echo $section['id']; ?>">
                <div class="design-system__header">
                    <h5><?php echo $section['title']; ?></h5>
                    <div class="t-muted fs-13"><?php echo $section['description']; ?></div>
                </div>
                <div class="design-system__grid">
                    <?php foreach ($section['examples'] as $example) : ?>
                        <div class="design-system__example">
                            <div class="dg g-4"><?php echo $example['html'] ?? Form::renderFields($example['fields']); ?></div>
                            <code class="design-system__caption"><?php echo $example['caption']; ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <div class="design-system__submit card card-border">
            <div class="design-system__actions">
                <div class="t-muted fs-13 mr-auto">
                    <?php echo t('Submit checks the required fields and shows the data the form sends.'); ?>
                </div>
                <button type="reset" class="btn btn--outline"><?php echo t('Reset'); ?></button>
                <button type="submit" class="btn btn--primary"><?php echo t('Submit'); ?></button>
            </div>
            <pre class="design-system__data" u-show="submitted" u-text="submitted" hidden></pre>
        </div>
    </form>
</div>
