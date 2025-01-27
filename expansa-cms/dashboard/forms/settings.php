<?php

use app\Option;
use Expansa\I18n;
use Expansa\Safe;
use Expansa\Url;

/**
 * Website settings in dashboard
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'settings',
	[
		'class'   => 'tab tab--vertical',
		'x-data'  => sprintf( "tab('%s')", Safe::prop( $_GET['tab'] ?? 'general' ) ),
		'@change' => '$ajax("option/update")',
	],
	[
		[
			'name'    => 'general',
			'type'    => 'tab',
			'label'   => t( 'General' ),
			'caption' => t( 'main settings' ),
			'icon'    => 'ph ph-tree-structure',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'website',
					'label'         => t( 'Website' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'site[name]',
							'label'       => t( 'Name' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'A quick snapshot of your website' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => Option::get( 'site.name' ),
								'required'    => true,
								'placeholder' => t( 'e.g. Google' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'site[tagline]',
							'label'       => t( 'Tagline' ),
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
								'value'       => Option::get( 'site.tagline' ),
								'placeholder' => t( 'e.g. Just another Expansa site' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'site[language]',
							'label'       => t( 'Site Language' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Some description' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => Option::get( 'site.language' ),
								'x-select' => '{"showSearch": 1}',
							],
							'options' => I18n::getLanguagesOptions(),
						],
						[
							'type'        => 'text',
							'name'        => 'site[url]',
							'label'       => t( 'Site address (URL)' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'A quick snapshot of your website' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => Option::get( 'site.url' ),
								'placeholder' => t( 'e.g. Google' ),
								'required'    => true,
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'administrator',
					'label'         => t( 'Administrator' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'owner[email]',
							'label'       => t( 'Owner email address' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-at"></i>',
							'after'       => '',
							'instruction' => t( 'This address is used for admin purposes. If you change this, an email will be sent to your new address to confirm it. The new address will not become active until confirmed' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => Option::get( 'owner.email' ),
								'required' => true,
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'users',
					'label'         => t( 'Memberships' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => '',
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
							'attributes'  => [],
							'options'     => [
								'users[membership]' => [
									'content'     => t( 'Anyone can register' ),
									'icon'        => 'ph ph-user-list',
									'description' => t( 'An avatar is an image that can be associated with a user across multiple websites. In this area, you can choose to display avatars of users who interact with the site.' ),
									'checked'     => Option::get( 'users.membership', true ),
								],
								'users[moderate]' => [
									'content'     => t( 'Must confirm' ),
									'icon'        => 'ph ph-police-car',
									'description' => t( 'Configure the account verification algorithm.' ),
									'checked'     => Option::get( 'users.moderate', false ),
								],
							],
						],
						[
							'type'        => 'select',
							'name'        => 'users[role]',
							'label'       => '',
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'New user default role' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [
								[
									'field'    => 'users[membership]',
									'operator' => '==',
									'value'    => true,
								],
							],
							'attributes'  => [
								'value' => Option::get( 'users.role' ),
							],
							'options'     => [
								'subscriber'    => t( 'Subscriber' ),
								'contributor'   => t( 'Contributor' ),
								'author'        => t( 'Author' ),
								'editor'        => t( 'Editor' ),
								'administrator' => t( 'Administrator' ),
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'dates',
					'label'         => t( 'Dates & time' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'name'     => 'date-format',
							'type'     => 'custom',
							'callback' => function () {
								?>
								<div class="dg g-2">
									<label class="dg">
										<span class="df aic jcsb fw-500"><?php echo t( 'Date Format' ); ?></span>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">April 3, 2021</span> <code class="badge badge--dark-lt">F j, Y</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">2021-04-03</span> <code class="badge badge--dark-lt">Y-m-d</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">04/03/2021</span> <code class="badge badge--dark-lt">m/d/Y</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">Custom</span> <input class="mw-80" type="text" name="item">
									</label>
									<div class="fs-13 t-muted"><a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">Get formats list</a> on php.net</div>
								</div>
								<?php
							},
						],
						[
							'name'     => 'time-format',
							'type'     => 'custom',
							'callback' => function () {
								?>
								<div class="dg g-2">
									<label class="dg">
										<span class="df aic jcsb fw-500"><?php echo t( 'Time Format' ); ?></span>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">17:22</span> <code class="badge badge--dark-lt">H:i</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">5:22 PM</span> <code class="badge badge--dark-lt">g:i A</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">12:50am</span> <code class="badge badge--dark-lt">g:ia</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">Custom</span> <input class="mw-80" type="text" name="item">
									</label>
									<div class="fs-13 t-muted"><a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">Get full time formats list</a> on php.net</div>
								</div>
								<?php
							},
						],
						[
							'type'        => 'select',
							'name'        => 'week-starts-on',
							'label'       => t( 'Week Starts On' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">Get full time formats list</a> on php.net',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => Option::get( 'week-starts-on' ),
							],
							'options' => [
								'0' => t( 'Sunday' ),
								'1' => t( 'Monday' ),
								'2' => t( 'Tuesday' ),
								'3' => t( 'Wednesday' ),
								'4' => t( 'Thursday' ),
								'5' => t( 'Friday' ),
								'6' => t( 'Saturday' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'timezone',
							'label'       => t( 'Timezone' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Choose either a city in the same timezone as you or a UTC (Coordinated Universal Time) time offset.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => Option::get( 'timezone' ),
							],
							'options' => [
								'subscriber'    => t( 'Subscriber' ),
								'contributor'   => t( 'Contributor' ),
								'author'        => t( 'Author' ),
								'editor'        => t( 'Editor' ),
								'administrator' => t( 'Administrator' ),
							],
						],
					],
				],
			],
		],
		[
			'name'    => 'reading',
			'type'    => 'tab',
			'label'   => t( 'Reading' ),
			'caption' => t( 'displaying posts' ),
			'icon'    => 'ph ph-book-open-text',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'search_engine',
					'label'         => t( 'Search engine' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'discourage',
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
							'attributes'  => [],
							'options'     => [
								'discourage' => [
									'content'     => t( 'Discourage search engines from indexing this site' ),
									'icon'        => 'ph ph-globe-hemisphere-west',
									'description' => t( 'It is up to search engines to honor this request.' ),
									'checked'     => Option::get( 'discourage', false ),
								],
							],
						],
					],
				],
			],
		],
		[
			'name'    => 'discussions',
			'type'    => 'tab',
			'label'   => t( 'Discussions' ),
			'caption' => t( 'comments' ),
			'icon'    => 'ph ph-chats-circle',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'comments',
					'label'         => t( 'Post comments' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments',
							'label'       => t( 'Allow people to submit comments on new posts' ),
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
									'content'     => t( 'Allow people to submit comments on new posts' ),
									'icon'        => 'ph ph-chat-dots',
									'description' => t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.default_status', true ),
								],
								'comments[require_name_email]' => [
									'content'     => t( 'Comment author must fill out name and email' ),
									'icon'        => 'ph ph-textbox',
									'description' => t( 'If disabled, only the name is required' ),
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[registration]' => [
									'content'     => t( 'Users must be registered and logged in to comment' ),
									'icon'        => 'ph ph-browser',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[close_comments_for_old_posts]' => [
									'content'     => t( 'Automatically close comments on posts older than %s days', '<i class="field--sm field--outline"><samp class="field-item"><input type="number" name="close_comments_for_old_posts" value="14"></samp></i>' ),
									'icon'        => 'ph ph-hourglass-medium',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[thread_comments]' => [
									'content'     => t( 'Enable threaded (nested) comments %s levels deep', '<i class="field--sm field--outline"><samp class="field-item"><input type="number" name="close_comments_for_old_posts" value="5"></samp></i>' ),
									'icon'        => 'ph ph-stack',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'comments',
					'label'         => t( 'Email me whenever' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments[notify_posts]',
							'label'       => t( 'Anyone posts a comment' ),
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
								'comments[notify_posts]' => [
									'content'     => t( 'Anyone posts a comment' ),
									'icon'        => 'ph ph-chats',
									'description' => t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.default_status', true ),
								],
								'comments[notify_moderation]' => [
									'content'     => t( 'A comment is held for moderation' ),
									'icon'        => 'ph ph-detective',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'appears',
					'label'         => t( 'Before a comment appears' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments',
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
							'attributes'  => [],
							'options'     => [
								'comments[moderation]' => [
									'content'     => t( 'Comment must be manually approved' ),
									'icon'        => 'ph ph-chats',
									'description' => t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.moderation', true ),
								],
								'comments[previously_approved]' => [
									'content'     => t( 'Comment author must have a previously approved comment' ),
									'icon'        => 'ph ph-user-check',
									'description' => '',
									'checked'     => Option::get( 'comments.previously_approved' ),
								],
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'avatars',
					'label'         => t( 'Avatars displaying' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'avatars[show]',
							'label'       => t( 'Show Avatars' ),
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
								'avatars[show]' => [
									'content'     => t( 'Show Avatars' ),
									'icon'        => 'ph ph-smiley',
									'description' => t( 'An avatar is an image that can be associated with a user across multiple websites. In this area, you can choose to display avatars of users who interact with the site.' ),
									'checked'     => Option::get( 'avatars.show', true ),
								],
							],
						],
						[
							'type'        => 'radio',
							'name'        => 'avatars[type]',
							'label'       => t( 'Default Avatar' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their name.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => 'mystery',
							],
							'options'     => [
								'mystery' => [
									'image'   => Url::site( 'dashboard/assets/images/dashboard-light.svg' ),
									'title'   => t( 'Mystery Person' ),
									'content' => t( 'This theme will be active when your system is set to “light mode”' ),
								],
								'gravatar' => [
									'image'   => Url::site( 'dashboard/assets/images/dashboard-dark.svg' ),
									'title'   => t( 'Gravatar Logo' ),
									'content' => t( 'This theme will be active when your system is set to “night mode”' ),
								],
								'generated' => [
									'image'   => Url::site( 'dashboard/assets/images/dashboard-dark.svg' ),
									'title'   => t( 'Generated' ),
									'content' => t( 'This theme will be active when your system is set to “night mode”' ),
								],
							],
						],
					],
				],
			],
		],
		[
			'name'    => 'storage',
			'type'    => 'tab',
			'label'   => t( 'Storage' ),
			'caption' => t( 'media options' ),
			'icon'    => 'ph ph-lockers',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'images',
					'label'         => t( 'File uploading' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'select',
							'name'        => 'images[format]',
							'label'       => t( 'Convert images to format' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.format == \'' . Option::get( 'images.format' ) . '\'">Convert existing images</button>',
							'instruction' => t( 'If you change the value, the formats of already uploaded images will remain unchanged, the new value will be applied only to new images.' ),
							'tooltip'     => t( 'Can lead to loss of detail and image quality, as well as increase the cost of your hosting resources' ),
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => Option::get( 'images.format' ),
							],
							'options'     => [
								''     => t( 'Do not convert' ),
								'wepb' => t( 'Webp' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'images[organization]',
							'label'       => t( 'Files organization' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.organization.trim() == \'' . Option::get( 'images.organization', 'yearmonth' ) . '\'">Convert existing files</button>',
							'instruction' => t( 'Changing this value does not change the storage structure of existing files, but only for new files.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
							'options'     => [
								'yearmonth' => t( 'Into month- and year-based folders' ),
								'hash'      => t( 'Into hash-based folders' ),
							],
						],
					],
				],
			],
		],
		[
			'name'        => 'permalinks',
			'type'        => 'tab',
			'label'       => t( 'Permalinks' ),
			'caption'     => t( 'URLs structure' ),
			'description' => t( 'custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links' ),
			'icon'        => 'ph ph-link',
			'fields'      => [
				[
					'type'          => 'group',
					'name'          => 'dates',
					'label'         => t( 'Pages' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][single]',
							'label'       => t( 'Single page' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code class="badge"><i class="ph ph-link"></i> %s</code>', Url::site() ),
							'after'       => '',
							'instruction' => t( 'Select the permalink structure for your website. Including the %slug% tag makes links easy to understand, and can help your posts rank higher in search engines.' ),
							'tooltip'     => t( 'ZIP Code must be US or CDN format. You can use an extended ZIP+4 code to determine address more accurately.' ),
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => Option::get( 'permalinks.pages.single' ),
								'required' => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][categories]',
							'label'       => t( 'Categories' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code class="badge"><i class="ph ph-link"></i> %s</code>', Url::site() ),
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => Option::get( 'permalinks.pages.categories' ),
								'required' => true,
							],
						],
					],
				],
			],
		],
	]
);