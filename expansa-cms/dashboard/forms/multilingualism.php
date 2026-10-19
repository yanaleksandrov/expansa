<?php

use App\Models\Option;
use Expansa\Facades\I18n;

/**
 * Multilingual settings: the site language, the languages of the translations with their URL codes,
 * and the keys of the translation services. How the language shows in the URLs is set in the permalinks,
 * who translates in the roles.
 *
 * @since 2027.1
 */

// a saved key is never shown back, see Secrets::keep()
$saved = static fn ( string $option ): string => Option::get( $option ) ? t( 'Saved. Leave empty to keep it.' ) : '';

// languages to pick from with the code they get in the URLs by default
$catalog = [];
foreach ( I18n::getLanguages() as $language ) {
    $locale = (string) ( $language['locale'] ?? '' );
    if ( $locale !== '' ) {
        $catalog[ $locale ] = [
            'name' => $language['name'] === $language['native'] ? $language['name'] : "{$language['name']} - {$language['native']}",
            'code' => strtolower( (string) ( $language['iso_639_1'] ?? $locale ) ),
        ];
    }
}

$rows = [];
foreach ( (array) Option::get( 'multilingual.languages', [] ) as $locale => $code ) {
    $rows[] = [
        'locale' => (string) $locale,
        'name'   => $catalog[ $locale ]['name'] ?? (string) $locale,
        'code'   => (string) $code,
    ];
}

// SlimSelect rebuilds its options from its own data: taken languages are disabled by setData(), called after its click
$picker = sprintf(
    '{
        languages: %s,
        catalog: %s,
        add(picker) {
            const language = this.catalog[picker.value];
            if (!language || this.languages.some(item => item.locale === picker.value)) {
                return;
            }
            this.languages = [...this.languages, { locale: picker.value, ...language }];
            this.sync(picker);
        },
        remove(index, picker) {
            this.languages = this.languages.filter((item, i) => i !== index);
            this.sync(picker);
        },
        sync(picker) {
            picker._x_slimSelect?.setData([
                { text: %s, value: "", placeholder: true },
                ...Object.entries(this.catalog).map(([value, language]) => ({
                    value,
                    text: language.name,
                    disabled: this.languages.some(item => item.locale === value),
                })),
            ]);
            picker._x_slimSelect?.setSelected("", false);
        },
    }',
    json_encode( $rows, JSON_UNESCAPED_UNICODE ),
    json_encode( $catalog, JSON_UNESCAPED_UNICODE ),
    json_encode( t( 'Add a language' ), JSON_UNESCAPED_UNICODE )
);

return Expansa\Builders\Form::enqueue(
    'multilingualism',
    [
        'class'           => 'tab',
        'u-data'          => 'tab as tab',
        '@load'           => '$dirty.watch($el)',
        '@submit.prevent' => '$ajax.post("options/update", "", () => $dirty.remove($el))',
    ],
    [
        [
            'name'    => 'general',
            'type'    => 'tab',
            'label'   => t( 'General' ),
            'caption' => t( 'Main Settings' ),
            'icon'    => 'ph ph-translate',
            'fields'  => [
                [
                    'type'   => 'group',
                    'name'   => 'site-language',
                    'label'  => t( 'Site Language' ),
                    'fields' => [
                        [
                            'type'        => 'select',
                            'name'        => 'site[language]',
                            'error'       => 'site.language',
                            'label'       => t( 'Site Language' ),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t( 'Main language of the site. The dashboard of each user follows the language of their profile.' ),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'   => 'site.language',
                                'value'    => Option::get( 'site.language' ),
                                'u-select' => '{"showSearch": 1}',
                            ],
                            'options' => I18n::languageOptions(),
                        ],
                        [
                            'name'     => 'multilingual-languages',
                            'type'     => 'custom',
                            'callback' => static function () use ( $picker, $catalog ) {
                                ?>
                                <div class="dg g-2" u-data="<?php echo htmlspecialchars( $picker, ENT_QUOTES ); ?>">
                                    <div class="field-label"><?php echo t( 'Translation languages' ); ?></div>
                                    <div class="dg g-2">
                                        <template u-each="(language, i) in languages">
                                            <div class="df aic g-2">
                                                <input type="hidden" name="multilingual[languages][locale][]" u-prop="language.locale">
                                                <label class="field field-item">
                                                    <i class="ph ph-globe-hemisphere-west"></i>
                                                    <input type="text" :value="language.name" readonly tabindex="-1">
                                                </label>
                                                <label class="field field-item mw-80">
                                                    <input type="text" name="multilingual[languages][code][]" u-prop="language.code" placeholder="en" pattern="[a-z0-9\-]+" autocomplete="off" spellcheck="false" required>
                                                    <i class="ph ph-trash-simple t-red" @click="remove(i, $refs.picker)" title="<?php echo t_attr( 'Remove' ); ?>"></i>
                                                </label>
                                            </div>
                                        </template>
                                    </div>
                                    <label class="field field-item">
                                        <select u-ref="picker" u-select='{"showSearch": 1}' @load="setTimeout(() => sync($el))" @change="setTimeout(() => add($el))">
                                            <option value=""><?php echo t( 'Add a language' ); ?></option>
                                            <?php foreach ( $catalog as $locale => $language ) : ?>
                                                <option value="<?php echo htmlspecialchars( $locale ); ?>"><?php echo htmlspecialchars( $language['name'] ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <div class="field-instruction"><?php echo t( 'Languages the content is translated to. The code selects the language in the URLs, e.g. `:example`: Latin letters, digits and hyphens.', url( '/en/my-post/' ) ); ?></div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'        => 'api',
            'type'        => 'tab',
            'label'       => t( 'API Keys' ),
            'caption'     => t( 'Machine Translation' ),
            'description' => t( 'Third-party translation services are subject to their own terms of use and may incur costs from the provider.' ),
            'icon'        => 'ph ph-key',
            'fields'      => [
                [
                    'type'   => 'group',
                    'name'   => 'services',
                    'label'  => t( 'Translation Services' ),
                    'fields' => [
                        [
                            'type'        => 'password',
                            'name'        => 'multilingual[deepl][key]',
                            'error'       => 'multilingual.deepl.key',
                            'label'       => 'DeepL Translator',
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => $saved( 'multilingual.deepl.key' ),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'autocomplete' => 'new-password',
                            ],
                        ],
                        [
                            'type'        => 'password',
                            'name'        => 'multilingual[google][key]',
                            'error'       => 'multilingual.google.key',
                            'label'       => 'Google Translate',
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => $saved( 'multilingual.google.key' ),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'autocomplete' => 'new-password',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <div class="expansa-form-actions">
                    <div class="expansa-form-actions-caption">
                        <i class="ph ph-warning-circle"></i> <?php echo t( 'Unsaved changes' ); ?>
                    </div>
                    <div class="expansa-form-actions-buttons">
                        <button class="btn t-red" type="reset"><?php echo t( 'Discard' ); ?></button>
                        <button class="btn t-green" type="submit"><?php echo t( 'Save' ); ?></button>
                    </div>
                </div>
                <?php
            },
        ],
    ]
);
