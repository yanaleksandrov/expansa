<?php

use App\Models\Option;
use App\Models\User;
use App\Post\Type;
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
			<button type="button" class="btn btn--outline dif aic g-1" @click="$dialog.open('tmpl-media-library', { ...mediaLibraryDialog, multiple: false, type: 'image', onSelect(item) { if (item) { image = item.url; } } })">
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

return Expansa\Builders\Form::enqueue(
	'seo',
	[
		'class'           => 'tab',
		'u-data'          => 'tab',
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
					'type'          => 'group',
					'name'          => 'site-info',
					'label'         => t( 'Site Info' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
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
							'instruction' => t( 'This field updates the tagline in the general settings.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'site.tagline' ),
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
			'label'       => t( 'Site Representation' ),
			'caption'     => t( 'Site Representation' ),
			'description' => t( 'This info is intended to appear in Google\'s Knowledge Graph.' ),
			'icon'        => 'ph ph-buildings',
			'fields'      => [
				[
					'type'          => 'group',
					'name'          => 'represents',
					'label'         => t( 'Organization/Person' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
						[
							'type'        => 'radio',
							'name'        => 'seo[represents]',
							'error'       => 'seo.represents',
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
								'value' => $represents,
							],
							'options'     => [
								'organization' => [
									'content'     => t( 'Organization' ),
									'icon'        => 'ph ph-buildings',
									'description' => t( 'A company, a nonprofit, a school or another group of people.' ),
									'checked'     => $represents === 'organization',
								],
								'person'       => [
									'content'     => t( 'Person' ),
									'icon'        => 'ph ph-user',
									'description' => t( 'A personal website or a blog of one author.' ),
									'checked'     => $represents === 'person',
								],
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'organization',
					'label'         => t( 'Organization' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'conditions'    => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'fields'        => [
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
					'type'          => 'group',
					'name'          => 'person',
					'label'         => t( 'Person' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'conditions'    => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'person',
						],
					],
					'fields'        => [
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
					'type'          => 'group',
					'name'          => 'profiles',
					'label'         => t( 'Other Profiles' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'conditions'    => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'fields'        => [
						[
							'type'        => 'url',
							'name'        => 'seo[organization][facebook]',
							'error'       => 'seo.organization.facebook',
							'label'       => t( 'Facebook' ),
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
							'label'       => t( 'X' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-x-logo"></i>',
							'after'       => '',
							'instruction' => t( 'A username or a profile URL.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'seo.organization.x' ),
							],
						],
						[
							'type'        => 'url',
							'name'        => 'seo[organization][mastodon]',
							'error'       => 'seo.organization.mastodon',
							'label'       => t( 'Mastodon' ),
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
									<div class="field-label"><?php echo t( 'Other Profiles' ); ?></div>
									<div class="field" u-each="(profile, i) in profiles">
										<label class="field-item">
											<input type="url" name="seo[organization][profiles][]" u-prop="profile.url" placeholder="https://">
											<i class="ph ph-trash-simple t-red" @click="profiles = profiles.filter((removed, index) => index !== i)" title="<?php echo t_attr( 'Remove' ); ?>"></i>
										</label>
									</div>
									<div class="field-instruction"><?php echo t( 'Any other profiles of the organization on the web: YouTube, LinkedIn, Pinterest or Wikipedia.' ); ?></div>
									<div>
										<button type="button" class="btn btn--outline dif aic g-1" @click="profiles = [...profiles, { url: '' }]">
											<i class="ph ph-plus"></i> <?php echo t( 'Add Another Profile' ); ?>
										</button>
									</div>
								</div>
								<?php
							},
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'organization-info',
					'label'         => t( 'Additional Organization Info' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-2 sm:gtc-1',
					'conditions'    => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'fields'        => [
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
					'type'          => 'group',
					'name'          => 'organization-identifiers',
					'label'         => t( 'Organization Identifiers' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-2 sm:gtc-1',
					'conditions'    => [
						[
							'field'    => 'seo[represents]',
							'operator' => '==',
							'value'    => 'organization',
						],
					],
					'fields'        => array_map(
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
					'type'          => 'group',
					'name'          => 'robots',
					'label'         => t( 'robots.txt File' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
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
					'type'          => 'group',
					'name'          => 'sitemap',
					'label'         => t( 'XML Sitemap' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
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
					'type'          => 'group',
					'name'          => 'llms-file',
					'label'         => t( 'llms.txt File' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
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
