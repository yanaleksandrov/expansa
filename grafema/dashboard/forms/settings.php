<?php
use Grafema\I18n;
use Grafema\Url;
use Grafema\Option;
use Grafema\Sanitizer;

/**
 * Website settings in dashboard
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'settings',
	[
		'class'   => 'tab tab--vertical',
		'x-data'  => sprintf( "tab('%s')", Sanitizer::prop( $_GET['tab'] ?? 'general' ) ),
		'@change' => '$ajax("options/update")',
	],
	[
		[
			'name'    => 'general',
			'type'    => 'tab',
			'label'   => I18n::_t( 'General' ),
			'caption' => I18n::_t( 'main settings' ),
			'icon'    => 'ph ph-tree-structure',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'website',
					'label'         => I18n::_t( 'Website' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'site[name]',
							'label'       => I18n::_t( 'Name' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'A quick snapshot of your website' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => Option::get( 'site.name' ),
								'required'    => true,
								'placeholder' => I18n::_t( 'e.g. Google' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'site[tagline]',
							'label'       => I18n::_t( 'Tagline' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'In a few words, explain what this site is about' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => Option::get( 'site.tagline' ),
								'placeholder' => I18n::_t( 'e.g. Just another Grafema site' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'site[language]',
							'label'       => I18n::_t( 'Site Language' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'Some description' ),
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
							'label'       => I18n::_t( 'Site address (URL)' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'A quick snapshot of your website' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => Option::get( 'site.url' ),
								'placeholder' => I18n::_t( 'e.g. Google' ),
								'required'    => true,
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'administrator',
					'label'         => I18n::_t( 'Administrator' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'owner[email]',
							'label'       => I18n::_t( 'Owner email address' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-at"></i>',
							'after'       => '',
							'instruction' => I18n::_t( 'This address is used for admin purposes. If you change this, an email will be sent to your new address to confirm it. The new address will not become active until confirmed' ),
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
					'label'         => I18n::_t( 'Memberships' ),
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
									'content'     => I18n::_t( 'Anyone can register' ),
									'icon'        => 'ph ph-user-list',
									'description' => I18n::_t( 'An avatar is an image that can be associated with a user across multiple websites. In this area, you can choose to display avatars of users who interact with the site.' ),
									'checked'     => Option::get( 'users.membership', true ),
								],
								'users[moderate]' => [
									'content'     => I18n::_t( 'Must confirm' ),
									'icon'        => 'ph ph-police-car',
									'description' => I18n::_t( 'Configure the account verification algorithm.' ),
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
							'instruction' => I18n::_t( 'New user default role' ),
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
								'subscriber'    => I18n::_t( 'Subscriber' ),
								'contributor'   => I18n::_t( 'Contributor' ),
								'author'        => I18n::_t( 'Author' ),
								'editor'        => I18n::_t( 'Editor' ),
								'administrator' => I18n::_t( 'Administrator' ),
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'dates',
					'label'         => I18n::_t( 'Dates & time' ),
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
										<span class="df aic jcsb fw-500"><?php I18n::t( 'Date Format' ); ?></span>
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
										<span class="df aic jcsb fw-500"><?php I18n::t( 'Time Format' ); ?></span>
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
							'label'       => I18n::_t( 'Week Starts On' ),
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
								'0' => I18n::_t( 'Sunday' ),
								'1' => I18n::_t( 'Monday' ),
								'2' => I18n::_t( 'Tuesday' ),
								'3' => I18n::_t( 'Wednesday' ),
								'4' => I18n::_t( 'Thursday' ),
								'5' => I18n::_t( 'Friday' ),
								'6' => I18n::_t( 'Saturday' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'timezone',
							'label'       => I18n::_t( 'Timezone' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'Choose either a city in the same timezone as you or a UTC (Coordinated Universal Time) time offset.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => Option::get( 'timezone' ),
							],
							'options' => [
								'subscriber'    => I18n::_t( 'Subscriber' ),
								'contributor'   => I18n::_t( 'Contributor' ),
								'author'        => I18n::_t( 'Author' ),
								'editor'        => I18n::_t( 'Editor' ),
								'administrator' => I18n::_t( 'Administrator' ),
							],
						],
					],
				],
			],
		],
		[
			'name'    => 'reading',
			'type'    => 'tab',
			'label'   => I18n::_t( 'Reading' ),
			'caption' => I18n::_t( 'displaying posts' ),
			'icon'    => 'ph ph-book-open-text',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'search_engine',
					'label'         => I18n::_t( 'Search engine' ),
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
									'content'     => I18n::_t( 'Discourage search engines from indexing this site' ),
									'icon'        => 'ph ph-globe-hemisphere-west',
									'description' => I18n::_t( 'It is up to search engines to honor this request.' ),
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
			'label'   => I18n::_t( 'Discussions' ),
			'caption' => I18n::_t( 'comments' ),
			'icon'    => 'ph ph-chats-circle',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'comments',
					'label'         => I18n::_t( 'Post comments' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments',
							'label'       => I18n::_t( 'Allow people to submit comments on new posts' ),
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
									'content'     => I18n::_t( 'Allow people to submit comments on new posts' ),
									'icon'        => 'ph ph-chat-dots',
									'description' => I18n::_t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.default_status', true ),
								],
								'comments[require_name_email]' => [
									'content'     => I18n::_t( 'Comment author must fill out name and email' ),
									'icon'        => 'ph ph-textbox',
									'description' => I18n::_t( 'If disabled, only the name is required' ),
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[registration]' => [
									'content'     => I18n::_t( 'Users must be registered and logged in to comment' ),
									'icon'        => 'ph ph-browser',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[close_comments_for_old_posts]' => [
									'content'     => I18n::_f( 'Automatically close comments on posts older than %s days', '<i class="field--sm field--outline"><samp class="field-item"><input type="number" name="close_comments_for_old_posts" value="14"></samp></i>' ),
									'icon'        => 'ph ph-hourglass-medium',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[thread_comments]' => [
									'content'     => I18n::_f( 'Enable threaded (nested) comments %s levels deep', '<i class="field--sm field--outline"><samp class="field-item"><input type="number" name="close_comments_for_old_posts" value="5"></samp></i>' ),
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
					'label'         => I18n::_t( 'Email me whenever' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments[notify_posts]',
							'label'       => I18n::_t( 'Anyone posts a comment' ),
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
									'content'     => I18n::_t( 'Anyone posts a comment' ),
									'icon'        => 'ph ph-chats',
									'description' => I18n::_t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.default_status', true ),
								],
								'comments[notify_moderation]' => [
									'content'     => I18n::_t( 'A comment is held for moderation' ),
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
					'label'         => I18n::_t( 'Before a comment appears' ),
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
									'content'     => I18n::_t( 'Comment must be manually approved' ),
									'icon'        => 'ph ph-chats',
									'description' => I18n::_t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.moderation', true ),
								],
								'comments[previously_approved]' => [
									'content'     => I18n::_t( 'Comment author must have a previously approved comment' ),
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
					'label'         => I18n::_t( 'Avatars displaying' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'avatars[show]',
							'label'       => I18n::_t( 'Show Avatars' ),
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
									'content'     => I18n::_t( 'Show Avatars' ),
									'icon'        => 'ph ph-smiley',
									'description' => I18n::_t( 'An avatar is an image that can be associated with a user across multiple websites. In this area, you can choose to display avatars of users who interact with the site.' ),
									'checked'     => Option::get( 'avatars.show', true ),
								],
							],
						],
						[
							'type'        => 'radio',
							'name'        => 'avatars[type]',
							'label'       => I18n::_t( 'Default Avatar' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'For users without a custom avatar of their own, you can either display a generic logo or a generated one based on their name.' ),
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
									'title'   => I18n::_t( 'Mystery Person' ),
									'content' => I18n::_t( 'This theme will be active when your system is set to “light mode”' ),
								],
								'gravatar' => [
									'image'   => Url::site( 'dashboard/assets/images/dashboard-dark.svg' ),
									'title'   => I18n::_t( 'Gravatar Logo' ),
									'content' => I18n::_t( 'This theme will be active when your system is set to “night mode”' ),
								],
								'generated' => [
									'image'   => Url::site( 'dashboard/assets/images/dashboard-dark.svg' ),
									'title'   => I18n::_t( 'Generated' ),
									'content' => I18n::_t( 'This theme will be active when your system is set to “night mode”' ),
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
			'label'   => I18n::_t( 'Storage' ),
			'caption' => I18n::_t( 'media options' ),
			'icon'    => 'ph ph-lockers',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'images',
					'label'         => I18n::_t( 'File uploading' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'select',
							'name'        => 'images[format]',
							'label'       => I18n::_t( 'Convert images to format' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.format == \'' . Option::get( 'images.format' ) . '\'">Convert existing images</button>',
							'instruction' => I18n::_t( 'If you change the value, the formats of already uploaded images will remain unchanged, the new value will be applied only to new images.' ),
							'tooltip'     => I18n::_t( 'Can lead to loss of detail and image quality, as well as increase the cost of your hosting resources' ),
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => Option::get( 'images.format' ),
							],
							'options'     => [
								''     => I18n::_t( 'Do not convert' ),
								'wepb' => I18n::_t( 'Webp' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'images[organization]',
							'label'       => I18n::_t( 'Files organization' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.organization.trim() == \'' . Option::get( 'images.organization', 'yearmonth' ) . '\'">Convert existing files</button>',
							'instruction' => I18n::_t( 'Changing this value does not change the storage structure of existing files, but only for new files.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
							'options'     => [
								'yearmonth' => I18n::_t( 'Into month- and year-based folders' ),
								'hash'      => I18n::_t( 'Into hash-based folders' ),
							],
						],
					],
				],
			],
		],
		[
			'name'        => 'permalinks',
			'type'        => 'tab',
			'label'       => I18n::_t( 'Permalinks' ),
			'caption'     => I18n::_t( 'URLs structure' ),
			'description' => I18n::_t( 'custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links' ),
			'icon'        => 'ph ph-link',
			'fields'      => [
				[
					'type'          => 'group',
					'name'          => 'dates',
					'label'         => I18n::_t( 'Pages' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][single]',
							'label'       => I18n::_t( 'Single page' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code class="badge"><i class="ph ph-link"></i> %s</code>', Url::site() ),
							'after'       => '',
							'instruction' => I18n::_t( 'Select the permalink structure for your website. Including the %slug% tag makes links easy to understand, and can help your posts rank higher in search engines.' ),
							'tooltip'     => I18n::_t( 'ZIP Code must be US or CDN format. You can use an extended ZIP+4 code to determine address more accurately.' ),
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
							'label'       => I18n::_t( 'Categories' ),
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