<?php

use App\Models\Option;
use App\Models\User;
use App\Post\Type;
use App\Support\Permalinks;
use App\Support\Transliteration;
use Expansa\Facades\Db;

/**
 * SEO settings in dashboard
 *
 * @since 2027.1
 */

// picker of one image from the media library, the field keeps its URL
$image = static fn ( string $name, string $option, string $label, string $instruction = '' ): Closure => static function () use ( $name, $option, $label, $instruction ) {
	?>
	<div class="dg g-1" u-data="{ image: <?php echo htmlspecialchars( json_encode( (string) Option::get( $option ) ), ENT_QUOTES ); ?> }">
		<div class="field-label"><?php echo $label; ?></div>
		<div class="storage storage--field" u-show="image">
			<div class="storage__item">
				<img class="storage__image" :src="image" width="200" height="200" alt>
				<span class="storage__remove" @click.stop.prevent="image = ''" title="<?php echo t_attr( 'Remove image' ); ?>">
					<i class="ph ph-x"></i>
				</span>
			</div>
		</div>
		<div class="df g-2">
			<label class="btn btn--outline dif aic g-1">
				<i class="ph ph-upload-simple"></i> <?php echo t( 'Upload' ); ?>
				<input type="file" accept="image/*" hidden @change="$ajax.post('media/upload', $el.files).then(({posts}) => { image = posts?.[0]?.url || image; $el.value = ''; })">
			</label>
			<button type="button" class="btn btn--outline dif aic g-1" @click="$dialog.open('tmpl-media-library', { multiple: false, type: 'image' }).then(item => { if (item) { image = item.url; } })">
				<i class="ph ph-image"></i> <?php echo t( 'Choose from Library' ); ?>
			</button>
		</div>
		<input type="hidden" name="<?php echo $name; ?>" :value="image">
		<?php if ( $instruction ) : ?>
			<div class="field-instruction"><?php echo $instruction; ?></div>
		<?php endif; ?>
	</div>
	<?php
};

$separator  = (string) Option::get( 'seo.site.separator', 'dash' );
$represents = (string) Option::get( 'seo.represents', 'organization' );
$selection  = (string) Option::get( 'seo.llms.selection', 'automatic' );

$identifiers = [
	'vat'   => t( 'VAT ID' ),
	'tax'   => t( 'Tax ID' ),
	'iso'   => t( 'ISO 6523' ),
	'duns'  => t( 'DUNS' ),
	'lei'   => t( 'LEI Code' ),
	'naics' => t( 'NAICS' ),
];

$llmsPages = [
	'about'   => t( 'About Us Page' ),
	'contact' => t( 'Contact Page' ),
	'terms'   => t( 'Terms of Service Page' ),
	'privacy' => t( 'Privacy Policy Page' ),
];

$pages = array_column( Db::select( 'pages', [ 'id [Int]', 'title' ], [ 'status' => 'publish', 'ORDER' => [ 'title' => 'ASC' ], 'LIMIT' => 500 ] ) ?? [], 'title', 'id' );
$pages = [ '' => t( 'Select a page' ) ] + $pages;

// all public post types are in the sitemap until they are chosen
$sitemapTypes = Option::get( 'seo.sitemap.types', null );
$sitemap      = [];
foreach ( Type::fetch( [ 'public' => true ] ) as $type ) {
	if ( $type->key !== 'files' ) {
		$sitemap[ "seo[sitemap][types][$type->key]" ] = [
			'content' => $type->labelNamePlural,
			'icon'    => 'ph ph-files',
			'checked' => $sitemapTypes === null || in_array( $type->key, (array) $sitemapTypes, true ),
		];
	}
}

$transliterationMode = Option::get( 'permalinks.transliterate', false ) ? 'latin' : 'keep';
$urlLanguage         = Option::get( 'permalinks.language', 'directory' );
$siteHost            = (string) parse_url( (string) Option::get( 'site.url' ), PHP_URL_HOST );

// the language part of the URLs matters only when the site has translations
$urlLanguageGroups = (array) Option::get( 'multilingual.languages', [] ) === [] ? [] : [
	[
		'type'   => 'group',
		'name'   => 'language',
		'label'  => t( 'Language in URLs' ),
		'fields' => [
			[
				'type'        => 'radio',
				'name'        => 'permalinks[language]',
				'error'       => 'permalinks.language',
				'label'       => t( 'Language in URLs' ),
				'class'       => 'field field--ui',
				'label_class' => '',
				'reset'       => 0,
				'before'      => '',
				'after'       => '',
				'instruction' => t( 'Where the URLs of the translations show their language. The site language has none.' ),
				'tooltip'     => '',
				'copy'        => 0,
				'validator'   => '',
				'conditions'  => [],
				'attributes'  => [],
				'options'     => [
					'directory' => [
						'content'     => t( 'Directory' ),
						'icon'        => 'ph ph-folder-simple',
						'description' => t( 'For example, `:example`', url( '/en/my-post/' ) ),
						'checked'     => $urlLanguage !== 'subdomain',
					],
					'subdomain' => [
						'content'     => t( 'Subdomain' ),
						'icon'        => 'ph ph-globe-simple',
						'description' => t( 'For example, `:example`. Each subdomain has to point to the site.', "https://en.$siteHost/my-post/" ),
						'checked'     => $urlLanguage === 'subdomain',
					],
				],
			],
		],
	],
];

$transliterationScripts = [
	'latin'    => [
		'label'       => t( 'Latin' ),
		'lang'        => '',
		'dir'         => 'ltr',
		'groups'      => [
			'letters' => t( 'Letters with diacritics' ),
		],
	],
	'cyrillic' => [
		'label'       => t( 'Cyrillic' ),
		'lang'        => 'ru',
		'dir'         => 'ltr',
		'groups'      => [
			'letters'  => t( 'Letters' ),
			'extended' => t( 'Letters of other languages' ),
		],
	],
	'arabic'   => [
		'label'       => t( 'Arabic' ),
		'lang'        => 'ar',
		'dir'         => 'rtl',
		'groups'      => [
			'letters'  => t( 'Letters' ),
			'extended' => t( 'Letters of other languages' ),
			'marks'    => t( 'Diacritics' ),
			'digits'   => t( 'Digits' ),
		],
	],
	'hebrew'   => [
		'label'       => t( 'Hebrew' ),
		'lang'        => 'he',
		'dir'         => 'rtl',
		'groups'      => [
			'letters'  => t( 'Letters' ),
			'finals'   => t( 'Final forms' ),
			'extended' => t( 'Yiddish ligatures' ),
			'marks'    => t( 'Vowel points' ),
		],
	],
	'hangul'   => [
		'label'       => t( 'Hangul' ),
		'lang'        => 'ko',
		'dir'         => 'ltr',
		'groups'      => [
			'initials' => t( 'Initial consonants' ),
			'vowels'   => t( 'Vowels' ),
			'finals'   => t( 'Final consonants' ),
		],
	],
];

$transliterationModes = [
	'keep'  => [
		'content'     => t( 'Keep the letters' ),
		'icon'        => 'ph ph-globe-hemisphere-west',
		'description' => t( 'Slugs keep the letters of any script, e.g. `:example`', url( '/привет-мир/' ) ),
		'checked'     => $transliterationMode === 'keep',
	],
	'latin' => [
		'content'     => t( 'Transliterate to Latin' ),
		'icon'        => 'ph ph-translate',
		'description' => t( 'The letters of all scripts below are replaced with their values, e.g. `:example`', url( '/privet-mir/' ) ),
		'checked'     => $transliterationMode === 'latin',
	],
];

// letters of a script with the Latin inputs; diacritics are shown on a dotted circle
$transliterationLetters = static fn ( string $name, array $config ): Closure => static function () use ( $name, $config ) {
	$defaults = Transliteration::SCRIPTS[ $name ];
	foreach ( Transliteration::letters( $name ) as $group => $values ) :
		$combining = $group === 'marks';
		?>
		<div class="dg g-2">
			<div class="field-label"><?php echo $config['groups'][ $group ] ?? $group; ?></div>
			<div class="field transliteration">
				<?php foreach ( $values as $letter => $latin ) : ?>
					<label class="field-item" title="<?php echo t_attr( 'Default: :value', $defaults[ $group ][ $letter ] ?: '—' ); ?>">
						<span class="transliteration-letter" lang="<?php echo $config['lang']; ?>" dir="<?php echo $config['dir']; ?>"><?php echo ( $combining && preg_match( '/^\p{M}/u', $letter ) ? '◌' : '' ) . $letter; ?></span>
						<input type="text" name="permalinks[scripts][<?php echo $name; ?>][<?php echo $group; ?>][<?php echo $letter; ?>]" value="<?php echo $latin; ?>" maxlength="8" pattern="[a-z0-9]*" autocomplete="off" spellcheck="false">
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	endforeach;
};

$transliterationGroups = [];
foreach ( $transliterationScripts as $name => $config ) {
	$transliterationGroups[] = [
		'type'        => 'group',
		'name'        => "transliteration-$name",
		'label'       => $config['label'],
		'conditions'  => [
			[
				'field'    => 'permalinks[mode]',
				'operator' => '==',
				'value'    => 'latin',
			],
		],
		'fields'      => [
			[
				'name'     => "permalinks-$name",
				'type'     => 'custom',
				'callback' => $transliterationLetters( $name, $config ),
			],
		],
	];
}

return Expansa\Builders\Form::enqueue(
	'seo',
	[
		'class'           => 'tab',
		'u-data'          => 'tab as tab',
		'@load'           => '$dirty.watch($el)',
		'@submit.prevent' => '$ajax.post("options/update", "", () => $dirty.remove($el))',
	],
	[
		[
			'name'        => 'basics',
			'type'        => 'tab',
			'label'       => t( 'Site Basics' ),
			'caption'     => t( 'Site Basics' ),
			'description' => t( 'Configure the basics for your website.' ),
			'icon'        => 'ph ph-app-window',
			'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'site-info',
					'label'  => t( 'Site Info' ),
					'fields' => [
						[
							'type'        => 'text',
							'name'        => 'site[name]',
							'error'       => 'site.name',
							'label'       => t( 'Website Name' ),
							'class'       => '',
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
								'value'    => (string) Option::get( 'site.name' ),
								'required' => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'seo[site][alternate_name]',
							'error'       => 'seo.site.alternate_name',
							'label'       => t( 'Alternate Website Name' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Use the alternate website name for acronyms, or a shorter version of your website\'s name.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.site.alternate_name' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'site[tagline]',
							'error'       => 'site.tagline',
							'label'       => t( 'Tagline' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'In a few words, explain what this site is about.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'site.tagline' ),
								'placeholder' => t( 'e.g. Just another Expansa site' ),
							],
						],
						[
							'type'        => 'radio',
							'name'        => 'seo[site][separator]',
							'error'       => 'seo.site.separator',
							'label'       => t( 'Title Separator' ),
							'class'       => 'field field--button',
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
								'value' => $separator,
							],
							'options'     => [
								'dash' => [
									'content' => '-',
									'checked' => $separator === 'dash',
								],
								'ndash' => [
									'content' => '–',
									'checked' => $separator === 'ndash',
								],
								'mdash' => [
									'content' => '—',
									'checked' => $separator === 'mdash',
								],
								'colon' => [
									'content' => ':',
									'checked' => $separator === 'colon',
								],
								'middot' => [
									'content' => '·',
									'checked' => $separator === 'middot',
								],
								'bull' => [
									'content' => '•',
									'checked' => $separator === 'bull',
								],
								'star' => [
									'content' => '*',
									'checked' => $separator === 'star',
								],
								'sstar' => [
									'content' => '⋆',
									'checked' => $separator === 'sstar',
								],
								'pipe' => [
									'content' => '|',
									'checked' => $separator === 'pipe',
								],
								'tilde' => [
									'content' => '~',
									'checked' => $separator === 'tilde',
								],
								'laquo' => [
									'content' => '«',
									'checked' => $separator === 'laquo',
								],
								'raquo' => [
									'content' => '»',
									'checked' => $separator === 'raquo',
								],
								'gt' => [
									'content' => '&gt;',
									'checked' => $separator === 'gt',
								],
								'lt' => [
									'content' => '&lt;',
									'checked' => $separator === 'lt',
								],
							],
						],
						[
							'name'     => 'seo-site-image',
							'type'     => 'custom',
							'callback' => $image( 'seo[site][image]', 'seo.site.image', t( 'Site Image' ), t( 'This image is used as a fallback for posts and pages that have no images set.' ) ),
						],
					],
				],
			],
		],
		[
			'name'        => 'representation',
			'type'        => 'tab',
			'label'       => t( 'Identity' ),
			'caption'     => t( 'Site Representation' ),
			'description' => t( 'This info is intended to appear in Google\'s Knowledge Graph.' ),
			'icon'        => 'ph ph-buildings',
			'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'represents',
					'label'  => t( 'Organization/Person' ),
					'fields' => [
						[
							'type'        => 'button-group',
							'name'        => 'seo[represents]',
							'label'       => t( 'Graph data type' ),
							'class'       => '',
							'label_class' => '',
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Organization: a company, a nonprofit, a school or another group of people. Person: a personal website or a blog of one author.' ),
							'tooltip'     => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => $represents,
							],
							'options'     => [
								'organization' => t( 'Organization' ),
								'person'       => t( 'Person' ),
							],
						],
					],
				],
				[
					'type'       => 'group',
					'name'       => 'organization',
					'label'      => t( 'Organization' ),
					'conditions' => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'fields'     => [
						[
							'type'        => 'text',
							'name'        => 'seo[organization][name]',
							'error'       => 'seo.organization.name',
							'label'       => t( 'Organization Name' ),
							'class'       => '',
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
								'value' => (string) Option::get( 'seo.organization.name' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'seo[organization][alternate_name]',
							'error'       => 'seo.organization.alternate_name',
							'label'       => t( 'Alternate Organization Name' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Use the alternate organization name for acronyms, or a shorter version of your organization\'s name.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.organization.alternate_name' ),
							],
						],
						[
							'name'     => 'seo-organization-logo',
							'type'     => 'custom',
							'callback' => $image( 'seo[organization][logo]', 'seo.organization.logo', t( 'Organization Logo' ), t( 'Recommended size for this image is 696×696px.' ) ),
						],
					],
				],
				[
					'type'       => 'group',
					'name'       => 'person',
					'label'      => t( 'Person' ),
					'conditions' => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'person',
						],
					],
					'fields'     => [
						[
							'type'        => 'select',
							'name'        => 'seo[person][user]',
							'error'       => 'seo.person.user',
							'label'       => t( 'User' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'The name and the profile of this user represent the site.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => (string) Option::get( 'seo.person.user' ),
								'u-select' => '{"showSearch": 1}',
							],
							'options'     => [ '' => t( 'Select a user' ) ] + User::options(),
						],
						[
							'name'     => 'seo-person-image',
							'type'     => 'custom',
							'callback' => $image( 'seo[person][image]', 'seo.person.image', t( 'Personal Logo or Avatar' ) ),
						],
					],
				],
				[
					'type'       => 'group',
					'name'       => 'profiles',
					'label'      => t( 'Other Profiles' ),
					'conditions' => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'fields'     => [
						[
							'type'        => 'url',
							'name'        => 'seo[organization][facebook]',
							'error'       => 'seo.organization.facebook',
							'label'       => '',
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-facebook-logo"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'seo.organization.facebook' ),
								'placeholder' => 'https://www.facebook.com/example',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'seo[organization][x]',
							'error'       => 'seo.organization.x',
							'label'       => '',
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-x-logo"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'seo.organization.x' ),
								'placeholder' => t( 'https://x.com/username or @username' ),
							],
						],
						[
							'type'        => 'url',
							'name'        => 'seo[organization][mastodon]',
							'error'       => 'seo.organization.mastodon',
							'label'       => '',
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-mastodon-logo"></i>',
							'after'       => '',
							'instruction' => t( 'Link the site from the Mastodon profile to get it verified.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'seo.organization.mastodon' ),
								'placeholder' => 'https://mastodon.social/@example',
							],
						],
						[
							'name'     => 'seo-organization-profiles',
							'type'     => 'custom',
							'callback' => static function () {
								$profiles = array_map( static fn ( string $url ) => [ 'url' => $url ], (array) Option::get( 'seo.organization.profiles', [] ) );
								?>
								<div class="dg g-2" u-data="{ profiles: <?php echo htmlspecialchars( json_encode( $profiles ), ENT_QUOTES ); ?> }">
									<div>
										<template u-each="(profile, i) in profiles">
											<div class="field">
												<label class="field-item">
													<input type="url" name="seo[organization][profiles][]" u-prop="profile.url" placeholder="https://">
													<i class="ph ph-trash-simple t-red" @click="profiles = profiles.filter((removed, index) => index !== i)" title="<?php echo t_attr( 'Remove' ); ?>"></i>
												</label>
											</div>
										</template>
									</div>
									<div class="field-instruction"><?php echo t( 'Any other profiles of the organization on the web: YouTube, LinkedIn, Pinterest or Wikipedia.' ); ?></div>
									<button type="button" class="btn btn--outline" @click="profiles = [...profiles, { url: '' }]">
										<i class="ph ph-plus"></i> <?php echo t( 'Add Another Profile' ); ?>
									</button>
								</div>
								<?php
							},
						],
					],
				],
				[
					'type'       => 'group',
					'name'       => 'organization-info',
					'label'      => t( 'Additional Organization Info' ),
					'conditions' => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'columns'    => 2,
					'fields'     => [
						[
							'type'        => 'textarea',
							'name'        => 'seo[organization][description]',
							'error'       => 'seo.organization.description',
							'label'       => t( 'Organization Description' ),
							'class'       => 'field ga-2 sm:ga-1',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'The more details you share, the better search engines understand your website.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.organization.description' ),
								'rows'  => 3,
							],
						],
						[
							'type'        => 'email',
							'name'        => 'seo[organization][email]',
							'error'       => 'seo.organization.email',
							'label'       => t( 'Organization Email Address' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-at"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.organization.email' ),
							],
						],
						[
							'type'        => 'tel',
							'name'        => 'seo[organization][phone]',
							'error'       => 'seo.organization.phone',
							'label'       => t( 'Organization Phone Number' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-phone"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.organization.phone' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'seo[organization][legal_name]',
							'error'       => 'seo.organization.legal_name',
							'label'       => t( 'Organization\'s Legal Name' ),
							'class'       => '',
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
								'value' => (string) Option::get( 'seo.organization.legal_name' ),
							],
						],
						[
							'type'        => 'date',
							'name'        => 'seo[organization][founding_date]',
							'error'       => 'seo.organization.founding_date',
							'label'       => t( 'Organization\'s Founding Date' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-calendar-blank"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.organization.founding_date' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'seo[organization][employees]',
							'error'       => 'seo.organization.employees',
							'label'       => t( 'Number of Employees' ),
							'class'       => '',
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
								'value' => (string) Option::get( 'seo.organization.employees' ),
							],
							'options'     => [
								''          => t( 'Select a range' ),
								'1-10'      => '1–10',
								'11-50'     => '11–50',
								'51-200'    => '51–200',
								'201-500'   => '201–500',
								'501-1000'  => '501–1000',
								'1001-5000' => '1001–5000',
								'5001'      => t( 'More than 5000' ),
							],
						],
					],
				],
				[
					'type'       => 'group',
					'name'       => 'organization-identifiers',
					'label'      => t( 'Organization Identifiers' ),
					'conditions' => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'columns'    => 2,
					'fields'     => array_map(
						static fn ( string $key, string $label ) => [
							'type'        => 'text',
							'name'        => "seo[organization][identifiers][$key]",
							'error'       => "seo.organization.identifiers.$key",
							'label'       => $label,
							'class'       => '',
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
								'value' => (string) Option::get( "seo.organization.identifiers.$key" ),
							],
						],
						array_keys( $identifiers ),
						$identifiers
					),
				],
			],
		],
		[
			'name'        => 'crawling',
			'type'        => 'tab',
			'label'       => t( 'Crawling' ),
			'caption'     => t( 'Crawling' ),
			'description' => t( 'Tell search engines and other crawlers which pages of your site they may visit and where to find them.' ),
			'icon'        => 'ph ph-bug',
			'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'robots',
					'label'  => t( 'robots.txt File' ),
					'fields' => [
						...array_map(
							static fn ( string $name ) => [
								'type'        => 'hidden',
								'name'        => $name,
								'error'       => '',
								'label'       => '',
								'class'       => '',
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
									'type'  => 'hidden',
									'value' => 0,
								],
							],
							[ 'seo[robots][discourage]', 'seo[robots][ai]' ]
						),
						[
							'type'        => 'checkbox',
							'name'        => '',
							'error'       => '',
							'label'       => '',
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
								'u-prop' => '',
							],
							'options'     => [
								'seo[robots][discourage]' => [
									'content'     => t( 'Discourage Search Engines From Indexing This Site' ),
									'icon'        => 'ph ph-globe-hemisphere-west',
									'description' => t( 'Closes the whole site in robots.txt. It is up to search engines to honor this request.' ),
									'checked'     => (bool) Option::get( 'seo.robots.discourage', false ),
								],
								'seo[robots][ai]'         => [
									'content'     => t( 'Block AI Crawlers' ),
									'icon'        => 'ph ph-robot',
									'description' => t( 'Asks the crawlers that collect data for AI training, such as GPTBot, ClaudeBot and Google-Extended, not to visit the site.' ),
									'checked'     => (bool) Option::get( 'seo.robots.ai', false ),
								],
							],
						],
						[
							'type'        => 'textarea',
							'name'        => 'seo[robots][rules]',
							'error'       => 'seo.robots.rules',
							'label'       => t( 'Custom Rules' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Added after the default rules, which close the dashboard and the API. A wrong rule may close the whole site, e.g. "Disallow: /".' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [
								[
									'field'    => 'seo[robots][discourage]',
									'operator' => '!=',
									'value'    => true,
								],
							],
							'attributes'  => [
								'value'       => (string) Option::get( 'seo.robots.rules' ),
								'rows'        => 5,
								'placeholder' => "User-agent: *\nDisallow: /private/",
							],
						],
					],
				],
				[
					'type'   => 'group',
					'name'   => 'sitemap',
					'label'  => t( 'XML Sitemap' ),
					'fields' => [
						...array_map(
							static fn ( string $name ) => [
								'type'        => 'hidden',
								'name'        => $name,
								'error'       => '',
								'label'       => '',
								'class'       => '',
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
									'type'  => 'hidden',
									'value' => 0,
								],
							],
							[ 'seo[sitemap][enabled]', 'seo[sitemap][types][_]' ]
						),
						[
							'type'        => 'checkbox',
							'name'        => '',
							'error'       => '',
							'label'       => '',
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
								'u-prop' => '',
							],
							'options'     => [
								'seo[sitemap][enabled]' => [
									'content'     => t( 'Enable XML Sitemap' ),
									'icon'        => 'ph ph-tree-view',
									'description' => t( 'Lists the published content in sitemap.xml and adds its address to robots.txt, so search engines find new pages sooner.' ),
									'checked'     => (bool) Option::get( 'seo.sitemap.enabled', true ),
								],
							],
						],
						[
							'type'        => 'checkbox',
							'name'        => '',
							'error'       => '',
							'label'       => t( 'Content in the Sitemap' ),
							'class'       => 'field field--ui',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [
								[
									'field'    => 'seo[sitemap][enabled]',
									'operator' => '==',
									'value'    => true,
								],
							],
							'attributes'  => [
								'u-prop' => '',
							],
							'options'     => $sitemap,
						],
					],
				],
			],
		],
		[
			'name'        => 'llms',
			'type'        => 'tab',
			'label'       => t( 'llms.txt' ),
			'caption'     => t( 'llms.txt' ),
			'description' => t( 'Future-proof your website for visibility in AI tools like ChatGPT and Google Gemini. This helps them provide better, more accurate information about your site.' ),
			'icon'        => 'ph ph-robot',
			'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'llms-file',
					'label'  => t( 'llms.txt File' ),
					'fields' => [
						[
							'type'        => 'hidden',
							'name'        => 'seo[llms][enabled]',
							'error'       => '',
							'label'       => '',
							'class'       => '',
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
								'type'  => 'hidden',
								'value' => 0,
							],
						],
						[
							'type'        => 'checkbox',
							'name'        => '',
							'error'       => '',
							'label'       => '',
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
								'u-prop' => '',
							],
							'options'     => [
								'seo[llms][enabled]' => [
									'content'     => t( 'Enable llms.txt File Feature' ),
									'icon'        => 'ph ph-file-text',
									'description' => t( 'Generates and weekly updates an llms.txt file that lists a selection of your site\'s content.' ) . ' <a href="' . url( 'llms.txt' ) . '" target="_blank">' . t( 'View the llms.txt file' ) . '</a>',
									'checked'     => (bool) Option::get( 'seo.llms.enabled', false ),
								],
							],
						],
						[
							'type'        => 'radio',
							'name'        => 'seo[llms][selection]',
							'error'       => 'seo.llms.selection',
							'label'       => t( 'Page Selection' ),
							'class'       => 'field field--ui',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [
								[
									'field'    => 'seo[llms][enabled]',
									'operator' => '==',
									'value'    => true,
								],
							],
							'attributes'  => [
								'value' => $selection,
							],
							'options'     => [
								'automatic' => [
									'content'     => t( 'Automatic Page Selection' ),
									'icon'        => 'ph ph-magic-wand',
									'description' => t( 'The main pages and the latest content of the site.' ),
									'checked'     => $selection === 'automatic',
								],
								'manual'    => [
									'content'     => t( 'Manual Page Selection' ),
									'icon'        => 'ph ph-hand-pointing',
									'description' => t( 'Choose the pages included in the file.' ),
									'checked'     => $selection === 'manual',
								],
							],
						],
						...array_map(
							static fn ( string $key, string $label ) => [
								'type'        => 'select',
								'name'        => "seo[llms][pages][$key]",
								'error'       => "seo.llms.pages.$key",
								'label'       => $label,
								'class'       => '',
								'label_class' => '',
								'reset'       => 0,
								'before'      => '',
								'after'       => '',
								'instruction' => '',
								'tooltip'     => '',
								'copy'        => 0,
								'validator'   => '',
								'conditions'  => [
									[
										'field'    => 'seo[llms][enabled]',
										'operator' => '==',
										'value'    => true,
									],
									[
										'field'    => 'seo[llms][selection]',
										'operator' => '==',
										'value'    => 'manual',
									],
								],
								'attributes'  => [
									'value'    => (string) Option::get( "seo.llms.pages.$key" ),
									'u-select' => '{"showSearch": 1}',
								],
								'options'     => $pages,
							],
							array_keys( $llmsPages ),
							$llmsPages
						),
					],
				],
			],
		],
		[
			'name'        => 'permalinks',
			'type'        => 'tab',
			'label'       => t( 'Permalinks' ),
			'caption'     => t( 'URL Structure' ),
			'description' => t( 'Custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links.' ),
			'icon'        => 'ph ph-link',
			'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'structure',
					'label'  => t( 'Paths' ),
					'fields' => [
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][single]',
							'error'       => 'permalinks.pages.single',
							'label'       => t( 'Entries' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code><i class="ph ph-link"></i> %s</code>', url() ),
							'after'       => '',
							'instruction' => t( 'Tags: :tags. :slug is required: it is the slug under the slugs of the parents.', '%slug%, %id%, %type%', '%slug%' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'permalinks.pages.single', '' ) ?: Permalinks::DEFAULTS['single'],
								'placeholder' => Permalinks::DEFAULTS['single'],
								'required'    => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][categories]',
							'error'       => 'permalinks.pages.categories',
							'label'       => t( 'Categories and tags' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code><i class="ph ph-link"></i> %s</code>', url() ),
							'after'       => '',
							'instruction' => t( 'Tags: :tags. :slug is required.', '%slug%, %taxonomy%', '%slug%' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'permalinks.pages.categories', '' ) ?: Permalinks::DEFAULTS['categories'],
								'placeholder' => Permalinks::DEFAULTS['categories'],
								'required'    => true,
							],
						],
					],
				],
				...$urlLanguageGroups,
			],
		],
		[
			'name'        => 'transliteration',
			'type'        => 'tab',
			'label'       => t( 'Transliteration' ),
			'caption'     => t( 'Slug Transliteration' ),
			'description' => t( 'Slugs are made in Latin letters: each letter is replaced with its value. A saved slug changes when its entry is saved again.' ),
			'icon'        => 'ph ph-link-simple',
			'fields'      => [
				[
					'type'   => 'group',
					'name'   => 'mode',
					'label'  => t( 'Mode' ),
					'fields' => [
						[
							'type'        => 'radio',
							'name'        => 'permalinks[mode]',
							'error'       => 'permalinks.mode',
							'label'       => t( 'Mode' ),
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
								'value' => $transliterationMode,
							],
							'options'     => $transliterationModes,
						],
					],
				],
				[
					'type'   => 'group',
					'name'   => 'rules',
					'label'  => t( 'Own Rules' ),
					'fields' => [
						[
							'name'     => 'permalinks-rules',
							'type'     => 'custom',
							'callback' => static function () {
								$rules = [];
								foreach ( (array) Option::get( 'permalinks.rules', [] ) as $from => $to ) {
									$rules[] = [
										'from' => (string) $from,
										'to'   => (string) $to,
									];
								}
								?>
								<div class="dg g-2" u-data="{ rules: <?php echo htmlspecialchars( json_encode( $rules, JSON_UNESCAPED_UNICODE ), ENT_QUOTES ); ?> }">
									<div class="dg g-2">
										<template u-each="(rule, i) in rules">
											<div class="df aic g-2">
												<div class="field">
													<label class="field-item">
														<input type="text" name="permalinks[rules][from][]" u-prop="rule.from" placeholder="<?php echo t_attr( 'Text' ); ?>" autocomplete="off" spellcheck="false">
													</label>
												</div>
												<i class="ph ph-arrow-right t-muted"></i>
												<div class="field">
													<label class="field-item">
														<input type="text" name="permalinks[rules][to][]" u-prop="rule.to" placeholder="<?php echo t_attr( 'Replacement' ); ?>" pattern="[a-z0-9\-]*" autocomplete="off" spellcheck="false">
														<i class="ph ph-trash-simple t-red" @click="rules = rules.filter((removed, index) => index !== i)" title="<?php echo t_attr( 'Remove' ); ?>"></i>
													</label>
												</div>
											</div>
										</template>
									</div>
									<div class="field-instruction"><?php echo t( 'Any text and its replacement in Latin letters, digits or hyphens, e.g. `:from` to `:to`. Own rules go before the tables of the scripts and also work when the letters are kept; a longer text wins.', '&', 'and' ); ?></div>
									<button type="button" class="btn btn--outline" @click="rules = [...rules, { from: '', to: '' }]">
										<i class="ph ph-plus"></i> <?php echo t( 'Add Rule' ); ?>
									</button>
								</div>
								<?php
							},
						],
					],
				],
				...$transliterationGroups,
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
