<?php

use App\Models\Option;
use Expansa\Facades\I18n;
use Expansa\Facades\Role;
use Expansa\Facades\Safe;
use Expansa\Patterns\Registry;

/**
 * Website settings in dashboard
 *
 * @since 2025.1
 */

// a plain field of the Security, Mail and AI tabs: everything but the type, name, texts and attributes is the default;
// it shows the errors of its name with dots, mail[dkim][domain] → mail.dkim.domain
$field = static fn ( string $type, string $name, string $label, string $instruction, array $attributes = [] ): array => [
	'type'        => $type,
	'name'        => $name,
	'error'       => $type === 'hidden' ? '' : str_replace( [ '][', '[', ']' ], [ '.', '.', '' ], $name ),
	'label'       => $label,
	'class'       => '',
	'label_class' => '',
	'reset'       => 0,
	'before'      => '',
	'after'       => '',
	'instruction' => $instruction,
	'tooltip'     => '',
	'copy'        => 0,
	'validator'   => '',
	'conditions'  => [],
	'attributes'  => [ 'name' => $name, ...$attributes ],
];

// instruction of a secret field: the saved value is never shown back, see App\Support\Secrets
$saved = static fn ( string $option, string $instruction = '' ): string => Option::get( $option ) ? t( 'Saved. Leave empty to keep it.' ) : $instruction;

return Expansa\Facades\Form::enqueue(
	'settings',
	[
		'class'           => 'tab tab--vertical',
		'u-data'          => 'tab',
        'u-init'          => '$dirty.watch($el)',
        '@submit.prevent' => '$ajax.post("options/update", "", () => $dirty.remove($el))',
	],
	[
		[
			'name'    => 'general',
			'type'    => 'tab',
			'label'   => t( 'General' ),
			'caption' => t( 'Main Settings' ),
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
							'error'       => 'site.name',
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
								'u-prop' => 'site.name',
								'value'       => Option::get( 'site.name' ),
								'required'    => true,
								'placeholder' => t( 'e.g. Google' ),
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
								'u-prop' => 'site.tagline',
								'value'       => Option::get( 'site.tagline' ),
								'placeholder' => t( 'e.g. Just another Expansa site' ),
							],
						],
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
								'u-prop' => 'site.language',
								'value'    => Option::get( 'site.language' ),
								'u-select' => '{"showSearch": 1}',
							],
							'options' => I18n::languageOptions(),
						],
						[
							'type'        => 'text',
							'name'        => 'site[url]',
							'error'       => 'site.url',
							'label'       => t( 'Site Address (URL)' ),
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
								'u-prop' => 'site.url',
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
							'error'       => 'owner.email',
							'label'       => t( 'Owner Email Address' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-at"></i>',
							'after'       => '',
							'instruction' => t( 'This address is used for admin purposes. If you change it, we\'ll send an email to the new address to confirm it. The new address won\'t become active until it\'s confirmed.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'owner.email',
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
							'attributes'  => [ 'u-prop' => '' ],
							'options'     => [
								'users[membership]' => [
									'content'     => t( 'Anyone can register' ),
									'icon'        => 'ph ph-user-list',
									'description' => t( 'Allow visitors to create an account on the site.' ),
									'checked'     => Option::get( 'users.membership', true ),
								],
								'users[moderate]' => [
									'content'     => t( 'Must confirm' ),
									'icon'        => 'ph ph-police-car',
									'description' => t( 'Choose how new accounts are verified.' ),
									'checked'     => Option::get( 'users.moderate', false ),
								],
							],
						],
						[
							'type'        => 'select',
							'name'        => 'users[role]',
							'error'       => 'users.role',
							'label'       => t( 'Default Role' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Role given to people who sign up on the site.' ),
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
								'u-prop' => 'users.role',
								'value' => Option::get( 'users.role' ),
							],
							'options'     => array_map( static fn ( array $role ) => $role['name'], Role::all() ),
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'dates',
					'label'         => t( 'Date and Time' ),
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
										<span><input class="mr-2" type="radio" name="item">April 3, 2021</span> <code class="badge badge--sm badge--dark-lt">F j, Y</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">2021-04-03</span> <code class="badge badge--sm badge--dark-lt">Y-m-d</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">04/03/2021</span> <code class="badge badge--sm badge--dark-lt">m/d/Y</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">Custom</span> <input class="mw-80" type="text" name="item">
									</label>
									<div class="fs-13 t-muted"><a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">Get formats list</a> on php.net</div>
								</div>
								<?php
							},
							'attributes'  => [ 'u-prop' => 'dateFormat' ],
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
										<span><input class="mr-2" type="radio" name="item">17:22</span> <code class="badge badge--sm badge--dark-lt">H:i</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">5:22 PM</span> <code class="badge badge--sm badge--dark-lt">g:i A</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">12:50am</span> <code class="badge badge--sm badge--dark-lt">g:ia</code>
									</label>
									<label class="df aic jcsb">
										<span><input class="mr-2" type="radio" name="item">Custom</span> <input class="mw-80" type="text" name="item">
									</label>
									<div class="fs-13 t-muted"><a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">Get full time formats list</a> on php.net</div>
								</div>
								<?php
							},
							'attributes'  => [ 'u-prop' => 'timeFormat' ],
						],
						[
							'type'        => 'select',
							'name'        => 'week-starts-on',
							'error'       => 'week-starts-on',
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
								'u-prop' => 'weekStartsOn',
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
							'error'       => 'timezone',
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
								'u-prop'   => 'timezone',
								'u-select' => '{"showSearch": 1}',
								'value'    => Option::get( 'timezone', 'UTC' ),
							],
							'options'     => Registry::get( 'timezones' ),
						],
					],
				],
			],
		],
		[
			'name'    => 'reading',
			'type'    => 'tab',
			'label'   => t( 'Reading' ),
			'caption' => t( 'Displaying Posts' ),
			'icon'    => 'ph ph-book-open-text',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'search_engine',
					'label'         => t( 'Search Engine' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'discourage',
							'error'       => 'discourage',
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
							'attributes'  => [ 'u-prop' => 'discourage' ],
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
			'caption' => t( 'Comments' ),
			'icon'    => 'ph ph-chats-circle',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'comments',
					'label'         => t( 'Post Comments' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments',
							'error'       => 'comments',
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
							'attributes'  => [ 'u-prop' => 'comments' ],
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
									'description' => t( 'If disabled, only the name is required.' ),
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[registration]' => [
									'content'     => t( 'Users must be registered and logged in to comment' ),
									'icon'        => 'ph ph-browser',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[close_comments_for_old_posts]' => [
									'content'     => t( 'Automatically close comments on posts older than %s days', '<label class="field--xs field--outline"><samp class="field-item"><input type="number" name="close_comments_for_old_posts" value="14"></samp></label>' ),
									'icon'        => 'ph ph-hourglass-medium',
									'description' => '',
									'checked'     => Option::get( 'comments.default_status' ),
								],
								'comments[thread_comments]' => [
									'content'     => t( 'Enable threaded (nested) comments %s levels deep', '<label class="field--xs field--outline"><samp class="field-item"><input type="number" name="close_comments_for_old_posts" value="5"></samp></label>' ),
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
					'label'         => t( 'Email Me Whenever' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments[notify_posts]',
							'error'       => 'comments.notify_posts',
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
							'attributes'  => [ 'u-prop' => 'comments.notifyPosts' ],
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
					'label'         => t( 'Before a Comment Appears' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments',
							'error'       => 'comments',
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
							'attributes'  => [ 'u-prop' => 'comments' ],
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
					'label'         => t( 'Avatar Display' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'avatars[show]',
							'error'       => 'avatars.show',
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
							'attributes'  => [ 'u-prop' => 'avatars.show' ],
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
							'error'       => 'avatars.type',
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
								'u-prop' => 'avatars.type',
								'value' => 'mystery',
							],
							'options'     => [
								'mystery' => [
									'image'   => url( 'dashboard/assets/images/dashboard-light.svg' ),
									'title'   => t( 'Mystery Person' ),
									'content' => t( 'A generic silhouette for users without an avatar' ),
								],
								'gravatar' => [
									'image'   => url( 'dashboard/assets/images/dashboard-dark.svg' ),
									'title'   => t( 'Gravatar Logo' ),
									'content' => t( 'The Gravatar logo for users without an avatar' ),
								],
								'generated' => [
									'image'   => url( 'dashboard/assets/images/dashboard-dark.svg' ),
									'title'   => t( 'Generated' ),
									'content' => t( 'An avatar generated from the user’s name' ),
								],
							],
						],
					],
				],
			],
		],
		[
			'name'    => 'security',
			'type'    => 'tab',
			'label'   => t( 'Security' ),
			'caption' => t( 'Sign-In Options' ),
			'icon'    => 'ph ph-shield-check',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'sign-in-limits',
					'label'         => t( 'Password Guessing' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						$field( 'number', 'security[attempts]', t( 'Wrong Passwords Before a Lockout' ), t( 'Per browser that signed in before, or per login from unknown browsers; 0 turns the limit off.' ), [
							'value' => (int) Option::get( 'security.attempts', 5 ),
							'min'   => 0,
						] ),
						$field( 'number', 'security[ip_attempts]', t( 'Wrong Passwords per IP' ), t( 'From unknown browsers, for any login: stops one password tried on many accounts; 0 turns it off.' ), [
							'value' => (int) Option::get( 'security.ip_attempts', 50 ),
							'min'   => 0,
						] ),
						$field( 'number', 'security[lockout]', t( 'First Lockout, Minutes' ), t( 'Each next lockout is twice as long, up to a day.' ), [
							'value' => (int) Option::get( 'security.lockout', 15 ),
							'min'   => 1,
						] ),
						$field( 'number', 'security[log_days]', t( 'Keep the Security Log, Days' ), t( 'Sign-ins and changes shown in the profiles; 0 keeps them forever.' ), [
							'value' => (int) Option::get( 'security.log_days', 90 ),
							'min'   => 0,
						] ),
						$field( 'hidden', 'security[breached]', '', '', [ 'type' => 'hidden', 'value' => 0 ] ),
						$field( 'hidden', 'security[email_link]', '', '', [ 'type' => 'hidden', 'value' => 0 ] ),
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
							'attributes'  => [ 'u-prop' => '' ],
							'options'     => [
								'security[breached]' => [
									'content'     => t( 'Refuse Breached Passwords' ),
									'icon'        => 'ph ph-password',
									'description' => t( 'Checks new passwords against public breaches; only 5 characters of a hash leave the site.' ),
									'checked'     => (bool) Option::get( 'security.breached', true ),
								],
								'security[email_link]' => [
									'content'     => t( 'Sign In by an Email Link' ),
									'icon'        => 'ph ph-envelope-simple-open',
									'description' => t( 'A one-time link valid for 15 minutes; two-factor authentication still asks for its code.' ),
									'checked'     => (bool) Option::get( 'security.email_link', false ),
								],
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'two-factor',
					'label'         => t( 'Two-Factor Authentication' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						$field( 'hidden', 'security[two_factor_roles][_]', '', '', [ 'type' => 'hidden', 'value' => 0 ] ),
						[
							'type'        => 'checkbox',
							'name'        => '',
							'error'       => '',
							'label'       => t( 'Required for Roles' ),
							'class'       => 'field field--ui',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Users of these roles set up an authenticator app before they can use the dashboard.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [ 'u-prop' => '' ],
							'options'     => array_map(
								static fn ( array $role ) => [
									'content' => $role['name'],
									'icon'    => 'ph ph-user-circle-gear',
									'checked' => (bool) ( Option::get( 'security.two_factor_roles', [] )[ $role['key'] ] ?? false ),
								],
								array_combine(
									array_map( static fn ( string $key ) => "security[two_factor_roles][$key]", array_keys( Role::all() ) ),
									array_map( static fn ( string $key, array $role ) => $role + [ 'key' => $key ], array_keys( Role::all() ), Role::all() )
								)
							),
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'oauth',
					'label'         => t( 'Sign-In Providers' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						$field( 'text', 'oauth[google][client_id]', t( 'Google Client ID' ), t( 'Callback URL: :url', url( 'oauth/google/callback' ) ), [
							'value' => (string) Option::get( 'oauth.google.client_id' ),
						] ),
						$field( 'password', 'oauth[google][client_secret]', t( 'Google Client Secret' ), $saved( 'oauth.google.client_secret' ), [
							'autocomplete' => 'new-password',
						] ),
						$field( 'text', 'oauth[github][client_id]', t( 'GitHub Client ID' ), t( 'Callback URL: :url', url( 'oauth/github/callback' ) ), [
							'value' => (string) Option::get( 'oauth.github.client_id' ),
						] ),
						$field( 'password', 'oauth[github][client_secret]', t( 'GitHub Client Secret' ), $saved( 'oauth.github.client_secret' ), [
							'autocomplete' => 'new-password',
						] ),
						$field( 'text', 'oauth[openid][label]', t( 'OpenID Connect Provider Name' ), t( 'Any OpenID Connect provider, e.g. GitLab or Keycloak. Callback URL: :url', url( 'oauth/openid/callback' ) ), [
							'value' => (string) Option::get( 'oauth.openid.label' ),
						] ),
						$field( 'text', 'oauth[openid][issuer]', t( 'Issuer URL' ), t( 'e.g. https://gitlab.com' ), [
							'value' => (string) Option::get( 'oauth.openid.issuer' ),
						] ),
						$field( 'text', 'oauth[openid][client_id]', t( 'Client ID' ), '', [
							'value' => (string) Option::get( 'oauth.openid.client_id' ),
						] ),
						$field( 'password', 'oauth[openid][client_secret]', t( 'Client Secret' ), $saved( 'oauth.openid.client_secret' ), [
							'autocomplete' => 'new-password',
						] ),
					],
				],
			],
		],
		[
			'name'    => 'mail',
			'type'    => 'tab',
			'label'   => t( 'Mail' ),
			'caption' => t( 'Outgoing Email' ),
			'icon'    => 'ph ph-envelope-simple',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'smtp',
					'label'         => t( 'SMTP Server' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						$field( 'text', 'mail[host]', t( 'Server' ), t( 'Without a server the mail goes through PHP mail() and often lands in spam.' ), [
							'value'       => (string) Option::get( 'mail.host' ),
							'placeholder' => 'smtp.example.com',
						] ),
						$field( 'number', 'mail[port]', t( 'Port' ), t( 'Usually 465 for SSL and 587 for STARTTLS.' ), [
							'value' => (int) Option::get( 'mail.port', 465 ),
							'min'   => 1,
						] ),
						[
							...$field( 'select', 'mail[encryption]', t( 'Encryption' ), '', [
								'value' => (string) Option::get( 'mail.encryption', 'ssl' ),
							] ),
							'options' => [
								'ssl'  => 'SSL',
								'tls'  => 'STARTTLS',
								'none' => t( 'None' ),
							],
						],
						$field( 'text', 'mail[username]', t( 'Login' ), t( 'Leave empty if the server needs no login.' ), [
							'value'        => (string) Option::get( 'mail.username' ),
							'autocomplete' => 'off',
						] ),
						$field( 'password', 'mail[password]', t( 'Password' ), $saved( 'mail.password' ), [
							'autocomplete' => 'new-password',
						] ),
						$field( 'email', 'mail[from]', t( 'Sender Address' ), t( 'An address of the site domain, otherwise the mail lands in spam.' ), [
							'value'       => (string) Option::get( 'mail.from' ),
							'placeholder' => 'no-reply@example.com',
						] ),
						$field( 'text', 'mail[from_name]', t( 'Sender Name' ), t( 'The site name when empty.' ), [
							'value' => (string) Option::get( 'mail.from_name' ),
						] ),
						[
							'name'     => 'mail-test',
							'type'     => 'custom',
							'callback' => static function () {
								?>
								<div class="df aic g-3">
									<button class="btn btn--outline" type="button" @click="$ajax.post('options/mail-test')"><i class="ph ph-paper-plane-tilt"></i> <?php echo t( 'Send a Test Email' ); ?></button>
									<span class="fs-13 t-muted"><?php echo t( 'To your email, with the saved settings.' ); ?></span>
								</div>
								<?php
							},
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'dkim',
					'label'         => t( 'DKIM Signature' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						$field( 'text', 'mail[dkim][domain]', t( 'Domain' ), t( 'The site domain when empty.' ), [
							'value' => (string) Option::get( 'mail.dkim.domain' ),
						] ),
						$field( 'text', 'mail[dkim][selector]', t( 'Selector' ), t( 'Name of the DNS record with the public key, e.g. "mail" for mail._domainkey.' ), [
							'value' => (string) Option::get( 'mail.dkim.selector' ),
						] ),
						$field( 'textarea', 'mail[dkim][private]', t( 'Private Key' ), $saved( 'mail.dkim.private', t( 'PEM, begins with -----BEGIN PRIVATE KEY-----.' ) ), [
							'rows' => 4,
						] ),
						$field( 'password', 'mail[dkim][passphrase]', t( 'Key Passphrase' ), $saved( 'mail.dkim.passphrase', t( 'Only for an encrypted key.' ) ), [
							'autocomplete' => 'new-password',
						] ),
					],
				],
			],
		],
		[
			'name'    => 'ai',
			'type'    => 'tab',
			'label'   => t( 'AI' ),
			'caption' => t( 'Plugin Generation' ),
			'icon'    => 'ph ph-sparkle',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'ai-service',
					'label'         => t( 'AI Service' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						$field( 'url', 'ai[url]', t( 'Address' ), t( 'Any OpenAI-compatible Chat Completions API: Google Gemini, OpenRouter, a local Ollama at http://localhost:11434/v1/. Empty turns the generation off.' ), [
							'value'       => (string) Option::get( 'ai.url', App\Support\Ai::URL ),
							'placeholder' => App\Support\Ai::URL,
						] ),
						$field( 'text', 'ai[model]', t( 'Model' ), t( 'e.g. gemini-flash-latest, or a model ID of OpenRouter ending in :free.' ), [
							'value'       => (string) Option::get( 'ai.model', App\Support\Ai::MODEL ),
							'placeholder' => App\Support\Ai::MODEL,
						] ),
						$field( 'password', 'ai[key]', t( 'Service Key' ), $saved( 'ai.key', t( 'A local service needs none.' ) ), [
							'autocomplete' => 'new-password',
						] ),
						$field( 'hidden', 'ai[schemas]', '', '', [ 'type' => 'hidden', 'value' => 0 ] ),
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
							'attributes'  => [ 'u-prop' => '' ],
							'options'     => [
								'ai[schemas]' => [
									'content'     => t( 'Structured Output' ),
									'icon'        => 'ph ph-brackets-curly',
									'description' => t( 'Turn off for models without json_schema support: the schema then goes into the instructions.' ),
									'checked'     => (bool) Option::get( 'ai.schemas', true ),
								],
							],
						],
					],
				],
			],
		],
		[
			'name'    => 'roles',
			'type'    => 'tab',
			'label'   => t( 'Roles' ),
			'caption' => t( 'Permissions of Users' ),
			'icon'    => 'ph ph-users-three',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'roles',
					'label'         => t( 'Roles and Permissions' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'name'     => 'roles',
							'type'     => 'custom',
							'callback' => function () {
								$permissions = App\Support\RoleSettings::getPermissions();
								?>
								<div class="dg g-4">
									<div class="t-muted fs-13"><?php echo t( 'A user gets the permissions of all their roles. The administrator always keeps manage_options and users_edit. Built-in roles can be changed but not deleted.' ); ?></div>
									<?php foreach ( Role::all() as $key => $role ) : ?>
										<div class="dg g-2 p-4 card card-border">
											<div class="df aic fw g-2">
												<div class="field">
													<div class="field-item">
														<input type="text" name="roles[<?php echo $key; ?>][name]" value="<?php echo htmlspecialchars( $role['name'] ); ?>">
													</div>
												</div>
												<code class="fs-12"><?php echo $key; ?></code>
												<?php if ( ! in_array( $key, App\Support\RoleSettings::BUILT_IN, true ) ) : ?>
													<label class="df aic g-1 fs-13 t-red ml-auto"><input type="checkbox" name="roles[<?php echo $key; ?>][delete]" value="1"> <?php echo t( 'Delete' ); ?></label>
												<?php endif; ?>
											</div>
											<input type="hidden" name="roles[<?php echo $key; ?>][permissions][_]" value="0">
											<div class="df fw g-3 fs-13">
												<?php foreach ( $permissions as $permission ) : ?>
													<label class="df aic g-1">
														<input type="checkbox" name="roles[<?php echo $key; ?>][permissions][<?php echo $permission; ?>]" value="1"<?php echo in_array( $permission, $role['permissions'], true ) ? ' checked' : ''; ?>>
														<?php echo $permission; ?>
													</label>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endforeach; ?>
									<div class="df aic fw g-2">
										<div class="field">
											<div class="field-item">
												<input type="text" name="roles[__new][key]" placeholder="<?php echo t_attr( 'New role key, e.g. moderator' ); ?>">
											</div>
										</div>
										<div class="field">
											<div class="field-item">
												<input type="text" name="roles[__new][name]" placeholder="<?php echo t_attr( 'Display name' ); ?>">
											</div>
										</div>
										<span class="t-muted fs-13"><?php echo t( 'Save, then choose its permissions.' ); ?></span>
									</div>
								</div>
								<?php
							},
						],
					],
				],
			],
		],
		[
			'name'    => 'storage',
			'type'    => 'tab',
			'label'   => t( 'Storage' ),
			'caption' => t( 'Media Options' ),
			'icon'    => 'ph ph-lockers',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'images',
					'label'         => t( 'File Uploads' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'select',
							'name'        => 'images[format]',
							'error'       => 'images.format',
							'label'       => t( 'Convert Images To' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.format == \'' . Option::get( 'images.format' ) . '\'">Convert existing images</button>',
							'instruction' => t( 'Changing this setting only affects newly uploaded images. Existing images keep their current format.' ),
							'tooltip'     => t( 'May reduce image detail and quality, and increase your hosting costs' ),
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'images.format',
								'value' => Option::get( 'images.format' ),
							],
							'options'     => [
								''     => t( 'Do not convert' ),
								'wepb' => 'WebP',
							],
						],
						[
							'type'        => 'select',
							'name'        => 'images[organization]',
							'error'       => 'images.organization',
							'label'       => t( 'File Organization' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.organization.trim() == \'' . Option::get( 'images.organization', 'yearmonth' ) . '\'">Convert existing files</button>',
							'instruction' => t( 'Changing this setting only affects new files. Existing files stay where they are.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [ 'u-prop' => 'images.organization' ],
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
			'caption'     => t( 'URL Structure' ),
			'description' => t( 'Custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links.' ),
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
							'error'       => 'permalinks.pages.single',
							'label'       => t( 'Single Page' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code><i class="ph ph-link"></i> %s</code>', url() ),
							'after'       => '',
							'instruction' => t( 'Select the permalink structure for your website. Including the %slug% tag makes links easy to understand, and can help your posts rank higher in search engines.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'permalinks.pages.single',
								'value'    => Option::get( 'permalinks.pages.single' ),
								'required' => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][categories]',
							'error'       => 'permalinks.pages.categories',
							'label'       => t( 'Categories' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code><i class="ph ph-link"></i> %s</code>', url() ),
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'permalinks.pages.categories',
								'value'    => Option::get( 'permalinks.pages.categories' ),
								'required' => true,
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
						<i class="ph ph-warning-circle"></i> <?php echo t('Unsaved changes'); ?>
					</div>
					<div class="expansa-form-actions-buttons">
						<button class="btn t-red" type="reset"><?php echo t('Discard'); ?></button>
						<button class="btn t-green" type="submit"><?php echo t('Save'); ?></button>
					</div>
				</div>
                <?php
            },
        ],
	]
);