<?php

use App\Models\Options;
use Expansa\Facades\Safe;

$languages = Expansa\Patterns\Registry::get('languages');

$options = [];
foreach ($languages as $language) {
    $locale = strtolower($language['locale'] ?? '');
    $url    = url();
    $input  = '<label class="field--xs field--outline"><samp class="field-item dif h-2 mw-80"><input type="text" value="' . $locale .'"></samp></label>';

    $options[$language['locale']] = [
        'content'     => "{$language['name']} - {$language['native']}",
        'icon'        => 'ph ph-globe-hemisphere-west',
        'description' => t('Language code:') . "<code>$url</code> $input <code>/my-post/</code>",
        'checked'     => false,
    ];
}

/**
 * Profile page.
 *
 * @since 2025.1
 */
return Expansa\Facades\Form::enqueue(
    'multilingual-settings',
    [
        'class'   => 'tab tab--vertical',
        'x-data'  => sprintf("tab('%s')", Safe::prop($_GET['tab'] ?? 'general')),
        '@change' => '$ajax("user/update")',
    ],
    [
        [
            'name'    => 'general',
            'type'    => 'tab',
            'label'   => t( 'General' ),
            'caption' => t( 'main settings' ),
            'icon'    => 'ph ph-translate',
            'fields'  => [
                [
                    'type'          => 'group',
                    'name'          => 'comments',
                    'label'         => t( 'URL modifications' ),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-7 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'radio',
                            'name'        => 'site[language]',
                            'label'       => t( 'URL modifications' ),
                            'class'       => 'field field--ui',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t( 'Decide how your URLs will look like' ),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'value' => Options::get( 'site.language' ),
                            ],
                            'options' => [
                                'comments[default_status]' => [
                                    'content'     => t( 'The language is set from the directory name in pretty permalinks' ),
                                    'icon'        => 'ph ph-link',
                                    'description' => t('Example:') . '<code>' . url('/en/my-post/') . '</code>',
                                    'checked'     => Options::get( 'comments.default_status', true ),
                                ],
                                'comments[require_name_email]' => [
                                    'content'     => t( 'The language is set from the subdomain name in pretty permalinks' ),
                                    'icon'        => 'ph ph-link',
                                    'description' => t('Example:') . '<code>https://en.greenapple.jewelry/my-post/</code>',
                                    'checked'     => Options::get( 'comments.default_status', true ),
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'comments',
                    'label'         => t( 'Allow access for' ),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-7 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'checkbox',
                            'name'        => 'comments',
                            'label'       => t( 'Allow access for' ),
                            'class'       => 'field field--ui',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => '',
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [],
                            'options'     => [
                                'comments[default_status]' => [
                                    'content'     => t( 'Editor' ),
                                    'icon'        => 'ph ph-person-simple-run',
                                    'description' => t( 'It is up to search engines to honor this request.' ),
                                    'checked'     => Options::get( 'comments.default_status', true ),
                                ],
                                'comments[require_name_email]' => [
                                    'content'     => t( 'Author' ),
                                    'icon'        => 'ph ph-person-simple-run',
                                    'description' => t( 'It is up to search engines to honor this request.' ),
                                    'checked'     => Options::get( 'comments.default_status', true ),
                                ],
                                'comments[registration]' => [
                                    'content'     => t( 'Subscriber' ),
                                    'icon'        => 'ph ph-person-simple-run',
                                    'description' => t( 'It is up to search engines to honor this request.' ),
                                    'checked'     => Options::get( 'comments.default_status', true ),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'        => 'languages',
            'type'        => 'tab',
            'label'       => t('Languages'),
            'caption'     => t('Active languages'),
            'description' => t('Third-party translation services are subject to their own terms of use and may incur costs from the provider'),
            'icon'        => 'ph ph-globe-hemisphere-west',
            'fields'      => [
                [
                    'type'        => 'checkbox',
                    'name'        => 'site[language]',
                    'label'       => t( 'URL modifications' ),
                    'class'       => 'field field--ui',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => '',
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'value' => Options::get( 'site.language' ),
                    ],
                    'options' => $options,
                ],
            ],
        ],
        [
            'name'        => 'api',
            'type'        => 'tab',
            'label'       => t( 'API keys' ),
            'caption'     => t( 'Machine Translation' ),
			'description' => t('Third-party translation services are subject to their own terms of use and may incur costs from the provider'),
            'icon'        => 'ph ph-key',
            'fields'      => [
				[
                    'type'          => 'group',
                    'name'          => 'comments',
                    'label'         => t( 'Allow access for' ),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-7 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'text',
                            'name'        => 'site[tagline]',
                            'label'       => t( 'DeepL Translator' ),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t( 'In a few words, explain what this site is about' ),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'value'       => Options::get( 'site.tagline' ),
                                'placeholder' => t( 'e.g. Just another Expansa site' ),
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'site[tagline]',
                            'label'       => t( 'Google Translate' ),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t( 'In a few words, explain what this site is about' ),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'value'       => Options::get( 'site.tagline' ),
                                'placeholder' => t( 'e.g. Just another Expansa site' ),
                            ],
                        ],
                    ]
				]
            ],
        ],
    ]
);