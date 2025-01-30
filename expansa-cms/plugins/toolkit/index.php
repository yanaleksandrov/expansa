<?php

declare(strict_types=1);

use Expansa\Builders\Tree;
use Expansa\Extensions\Plugin;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Is;

return new class extends Plugin
{
    public function __construct()
    {
        $this
            ->setName('Toolkit')
            ->setVersion('2024.9')
            ->setAuthor('Expansa Team')
            ->setDescription(t('The developer tools panel for Expansa'));
    }

    public function boot(): void
    {
        if (! Is::dashboard()) {
            return;
        }

        Asset::enqueue('toolkit-main', '/plugins/toolkit/assets/css/main.css');

        Hook::add('expansa_view_part', function ($filepath) {
            if ($filepath === EX_DASHBOARD . 'views/fields-builder.php') {
                $filepath = __DIR__ . '/views/fields-builder.php';
            }
            if ($filepath === EX_DASHBOARD . 'views/forms-builder.php') {
                $filepath = __DIR__ . '/views/forms-builder.php';
            }
            return $filepath;
        });

        Tree::attach('dashboard-main-menu', fn (Tree $tree) => $tree->addItems(
            [
                [
                    'id'           => 'toolkit',
                    'url'          => 'forms-builder',
                    'title'        => t('Dev toolkit'),
                    'capabilities' => ['manage_options'],
                    'icon'         => 'ph ph-brackets-curly',
                    'position'     => 800,
                ],
                [
                    'id'           => 'fields-builder',
                    'url'          => 'fields-builder',
                    'title'        => t('Fields builder'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'toolkit',
                ],
                [
                    'id'           => 'forms-builder',
                    'url'          => 'forms-builder',
                    'title'        => t('Forms builder'),
                    'capabilities' => ['manage_options'],
                    'icon'         => '',
                    'position'     => 0,
                    'parent_id'    => 'toolkit',
                ],
            ]
        ));

        /*
         * Sign In form
         *
         * @since 2025.1
         */
        \Expansa\Facades\Form::enqueue(
            'builder/fields',
            [
                'class'  => 'dg p-7 g-7',
                'x-data' => "{type: '', tab:'general'}",
            ],
            [
                [
                    'name'    => 'manage',
                    'type'    => 'group',
                    'label'   => t('Managing fields'),
                    'class'   => '',
                    'columns' => '',
                    'fields'  => [
                        [
                            'name'        => 'general',
                            'type'        => 'tab',
                            'label'       => t('General'),
                            'caption'     => '',
                            'description' => '',
                            'icon'        => 'ph ph-cube',
                            'fields'      => [
                                [
                                    'type'        => 'select',
                                    'label'       => t('Field Type'),
                                    'name'        => 'type',
                                    'value'       => '',
                                    'placeholder' => '',
                                    'class'       => 'df aic fs-12 t-muted',
                                    'reset'       => 0,
                                    'required'    => 0,
                                    'before'      => '',
                                    'after'       => '',
                                    'tooltip'     => '',
                                    'instruction' => '',
                                    'attributes'  => [],
                                    'options'     => [
                                        'main'       => [
                                            'label'   => t('Basic'),
                                            'options' => [
                                                'input'     => [
                                                    'content' => t('Text'),
                                                    'icon'    => 'ph ph-text-t',
                                                ],
                                                'textarea'  => [
                                                    'content' => t('Text Area'),
                                                    'icon'    => 'ph ph-textbox',
                                                ],
                                                'number'    => [
                                                    'content' => t('Number'),
                                                    'icon'    => 'ph ph-hash',
                                                ],
                                                'range'     => [
                                                    'content' => t('Range (TODO)'),
                                                    'icon'    => 'ph ph-hash',
                                                ],
                                                'email'     => [
                                                    'content' => t('Email (TODO)'),
                                                    'icon'    => 'ph ph-at',
                                                ],
                                                'url'       => [
                                                    'content' => t('URL (TODO)'),
                                                    'icon'    => 'ph ph-link',
                                                ],
                                                'password'  => [
                                                    'content' => t('Password'),
                                                    'icon'    => 'ph ph-password',
                                                ],
                                                'submit'    => [
                                                    'content' => t('Submit Button'),
                                                    'icon'    => 'ph ph-paper-plane-tilt',
                                                ],
                                                'date'      => [
                                                    'content' => t('Date Picker (TODO)'),
                                                    'icon'    => 'ph ph-calendar',
                                                ],
                                                'date_time' => [
                                                    'content' => t('Date & Time Picker (TODO)'),
                                                    'icon'    => 'ph ph-calendar',
                                                ],
                                                'time'      => [
                                                    'content' => t('Time Picker (TODO)'),
                                                    'icon'    => 'ph ph-clock-countdown',
                                                ],
                                                'color'     => [
                                                    'content' => t('Color Picker (TODO)'),
                                                    'icon'    => 'ph ph-swatches',
                                                ],
                                            ],
                                        ],
                                        'content'    => [
                                            'label'   => t('Content'),
                                            'options' => [
                                                'image'    => [
                                                    'content' => t('Image'),
                                                    'icon'    => 'ph ph-image-square',
                                                ],
                                                'media'    => [
                                                    'content' => t('Media'), // TODO: gallery instead
                                                    'icon'    => 'ph ph-images-square',
                                                ],
                                                'editor'   => [
                                                    'content' => t('WYSIWYG editor (TODO)'),
                                                    'icon'    => 'ph ph-image-square',
                                                ],
                                                'uploader' => [
                                                    'content' => t('Uploader'),
                                                    'icon'    => 'ph ph-paperclip',
                                                ],
                                                'progress' => [
                                                    'content' => t('Progress'),
                                                    'icon'    => 'ph ph-spinner-gap',
                                                ],
                                            ],
                                        ],
                                        'choice'     => [
                                            'label'   => t('Choice'),
                                            'options' => [
                                                'select'   => [
                                                    'content' => t('Select'),
                                                    'icon'    => 'ph ph-list-checks',
                                                ],
                                                'checkbox' => [
                                                    'content' => t('Checkbox'),
                                                    'icon'    => 'ph ph-toggle-left',
                                                ],
                                                'radio'    => [
                                                    'content' => t('Radio Button'),
                                                    'icon'    => 'ph ph-radio-button',
                                                ],
                                            ],
                                        ],
                                        'relations'  => [
                                            'label'   => t('Relations'),
                                            'options' => [
                                                'link' => [
                                                    'content' => t('Link (TODO)'),
                                                    'icon'    => 'ph ph-link-simple',
                                                ],
                                                'post' => [
                                                    'content' => t('Post Object (TODO)'),
                                                    'icon'    => 'ph ph-note',
                                                ],
                                                'user' => [
                                                    'content' => t('User (TODO)'),
                                                    'icon'    => 'ph ph-user',
                                                ],
                                            ],
                                        ],
                                        'additional' => [
                                            'label'   => t('Layout'),
                                            'options' => [
                                                'details'  => [
                                                    'content' => t('Details'), // TODO: rename context menu
                                                    'icon'    => 'ph ph-dots-three-outline-vertical',
                                                ],
                                                'divider'  => [
                                                    'content' => t('Divider'),
                                                    'icon'    => 'ph ph-arrows-out-line-vertical',
                                                ],
                                                'step'     => [
                                                    'content' => t('Step'),
                                                    'icon'    => 'ph ph-steps',
                                                ],
                                                'tab'      => [
                                                    'content' => t('Tab'),
                                                    'icon'    => 'ph ph-tabs',
                                                ],
                                                'repeater' => [
                                                    'content' => t('Repeater (TODO)'),
                                                    'icon'    => 'ph ph-infinity',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'conditions'  => [],
                                ],
                                [
                                    'name'        => 'label',
                                    'type'        => 'text',
                                    'label'       => t('Field Label'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'before'      => '',
                                    'tooltip'     => '',
                                    'instruction' => t('This is the name which will appear on the EDIT page'),
                                    'attributes'  => [
                                        'value'       => 'Title',
                                        'placeholder' => t('Field label'),
                                    ],
                                ],
                                [
                                    'name'        => 'name',
                                    'type'        => 'text',
                                    'label'       => t('Field Name'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'before'      => '',
                                    'tooltip'     => '',
                                    'instruction' => t('Single word, no spaces. Underscores and dashes allowed'),
                                    'attributes'  => [
                                        'value'       => '',
                                        'placeholder' => t('Field label'),
                                    ],
                                ],
                                [
                                    'name'        => 'value',
                                    'type'        => 'text',
                                    'label'       => t('Default Value'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'before'      => '',
                                    'tooltip'     => '',
                                    'instruction' => t('Appears when creating a new post'),
                                    'attributes'  => [
                                        'value' => '',
                                    ],
                                ],
                                [
                                    'name'        => 'options',
                                    'type'        => 'textarea',
                                    'label'       => t('Options'),
                                    'class'       => 'df aic fs-12 t-muted',
                                    'value'       => 'red:Red',
                                    'before'      => '',
                                    'after'       => '',
                                    'tooltip'     => '',
                                    'limits'      => 0,
                                    'required'    => 0,
                                    'placeholder' => t('Placeholder text'),
                                    'instruction' => t('Enter each choice on a new line. You must specify both the value and the label as follows: red:Red'),
                                    'conditions'  => [
                                        [
                                            'field'    => 'type',
                                            'operator' => 'contains', // value contains
                                            'value'    => ['select'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name'        => 'validation',
                            'type'        => 'tab',
                            'label'       => t('Validation'),
                            'caption'     => '',
                            'description' => '',
                            'icon'        => 'ph ph-shield-check',
                            'fields'      => [
                                [
                                    'type'        => 'checkbox',
                                    'label'       => t('Required'),
                                    'name'        => 'required',
                                    'value'       => '',
                                    'placeholder' => '',
                                    'class'       => '',
                                    'reset'       => 0,
                                    'required'    => 0,
                                    'before'      => '',
                                    'after'       => '',
                                    'tooltip'     => '',
                                    'instruction' => t('The form will not be saved if it is not filled in'),
                                    'attributes'  => [],
                                    'conditions'  => [],
                                    'options'     => [],
                                ],
                            ],
                        ],
                        [
                            'name'        => 'presentation',
                            'type'        => 'tab',
                            'label'       => t('Presentation'),
                            'caption'     => '',
                            'description' => '',
                            'icon'        => 'ph ph-presentation-chart',
                            'fields'      => [
                                [
                                    'name'        => 'label_class',
                                    'type'        => 'text',
                                    'label'       => t('Label class'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'before'      => '',
                                    'tooltip'     => '',
                                    'instruction' => '',
                                    'attributes'  => [
                                        'placeholder' => t('e.g. df aic fs-12 t-muted'),
                                    ],
                                ],
                                [
                                    'name'        => 'before',
                                    'type'        => 'text',
                                    'label'       => t('Before content'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'before'      => '',
                                    'tooltip'     => '',
                                    'instruction' => '',
                                    'attributes'  => [
                                        'placeholder' => t('e.g. <i class="ph ph-bug"></i>'),
                                    ],
                                ],
                                [
                                    'name'        => 'after',
                                    'type'        => 'text',
                                    'label'       => t('After content'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'before'      => '',
                                    'tooltip'     => '',
                                    'instruction' => '',
                                    'attributes'  => [
                                        'placeholder' => t('e.g. Mb'),
                                    ],
                                ],
                                [
                                    'name'        => 'reset',
                                    'type'        => 'select',
                                    'label'       => t('Show reset button'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'value'       => '',
                                    'reset'       => false,
                                    'attributes'  => [],
                                    'options'     => [
                                        'yes' => t('Yes'),
                                        'no'  => t('No'),
                                    ],
                                ],
                                [
                                    'name'        => 'copy',
                                    'type'        => 'select',
                                    'label'       => t('Show copy button'),
                                    'label_class' => 'df aic fs-12 t-muted',
                                    'value'       => '',
                                    'reset'       => false,
                                    'attributes'  => [],
                                    'options'     => [
                                        'yes' => t('Yes'),
                                        'no'  => t('No'),
                                    ],
                                ],
                                [
                                    'name'        => 'description',
                                    'type'        => 'textarea',
                                    'value'       => '',
                                    'default'     => '',
                                    'label'       => t('Description'),
                                    'class'       => 'df aic fs-12 t-muted',
                                    'reset'       => false,
                                    'before'      => '',
                                    'after'       => '',
                                    'tooltip'     => '',
                                    'placeholder' => t('e.g. Mb'),
                                    'instruction' => t('Use this field to output instructions or additional explanations'),
                                    'attributes'  => [],
                                    'conditions'  => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function activate(): void
    {
        // TODO: Implement activate() method.
    }

    public function deactivate(): void
    {
        // TODO: Implement deactivate() method.
    }

    public function install(): void
    {
        // TODO: Implement install() method.
    }

    public function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }
};
