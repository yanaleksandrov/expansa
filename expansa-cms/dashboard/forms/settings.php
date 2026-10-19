<?php

use App\Models\Option;
use Expansa\Facades\I18n;
use Expansa\Facades\Role;
use Expansa\Facades\Safe;

/**
 * Website settings in dashboard
 *
 * @since 2027.1
 */

// instruction of a secret field: the saved value is never shown back, see App\Support\Secrets
$saved = static fn ( string $option, string $instruction = '' ): string => Option::get( $option ) ? t( 'Saved. Leave empty to keep it.' ) : $instruction;

// radio buttons of a date or time format, the "custom" one has a text field for its own format
$format = static function ( string $name, string $label, string $default, array $presets ): array {
	$saved   = (string) Option::get( $name, $default );
	$options = [];
	foreach ( $presets as $preset ) {
		$options[ $preset ] = [
			'content'     => date( $preset ),
			'description' => '<code>' . $preset . '</code>',
			'checked'     => $saved === $preset,
		];
	}

	$isCustom = ! isset( $options[ $saved ] );
	$value    = htmlspecialchars( $isCustom ? $saved : '', ENT_QUOTES );

	// the label does not check its radio by a click on the text field
	$options['custom'] = [
		'content'     => t( 'Custom' ),
		'description' => '<span class="field-item mt-1 mb-1"><input type="text" name="' . $name . '-custom" value="' . $value . '" placeholder="' . $default . '" @focus="$el.closest(\'label\').querySelector(\'[type=radio]\').click()"></span>'
			. t( 'Format tokens: [php.net](:link)', 'https://www.php.net/manual/en/datetime.format.php' ),
		'checked'     => $isCustom,
	];

	return [
		'type'        => 'radio',
		'name'        => $name,
		'error'       => $name,
		'label'       => $label,
		'class'       => 'field field--ui',
		'instruction' => '',
		'conditions'  => [],
		'attributes'  => [
			'value' => $isCustom ? 'custom' : $saved,
		],
		'options'     => $options,
	];
};

return Expansa\Builders\Form::enqueue(
	'settings',
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
			'icon'    => 'ph ph-tree-structure',
			'fields'  => [
				[
					'type'    => 'group',
					'name'    => 'website',
					'label'   => t( 'Website' ),
					'columns' => 2,
					'fields'  => [
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
								'u-prop'      => 'site.name',
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
								'u-prop'      => 'site.tagline',
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
								'u-prop'   => 'site.language',
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
								'u-prop'      => 'site.url',
								'value'       => Option::get( 'site.url' ),
								'placeholder' => t( 'e.g. Google' ),
								'required'    => true,
							],
						],
					],
				],
				[
					'type'    => 'group',
					'name'    => 'administrator',
					'label'   => t( 'Administrator' ),
					'columns' => 2,
					'fields'  => [
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
								'u-prop'   => 'owner.email',
								'value'    => Option::get( 'owner.email' ),
								'required' => true,
							],
						],
					],
				],
				[
					'type'   => 'group',
					'name'   => 'users',
					'label'  => t( 'Memberships' ),
					'fields' => [
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
								'value'  => Option::get( 'users.role' ),
							],
							'options'     => array_map( static fn ( array $role ) => $role['name'], Role::all() ),
						],
					],
				],
				[
					'type'    => 'group',
					'name'    => 'dates',
					'label'   => t( 'Date and Time' ),
					'columns' => 1,
					'fields'  => [
						$format( 'date-format', t( 'Date Format' ), 'F j, Y', [ 'F j, Y', 'Y-m-d', 'm/d/Y', 'd.m.Y' ] ),
						$format( 'time-format', t( 'Time Format' ), 'g:i a', [ 'H:i', 'g:i a', 'g:i A' ] ),
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
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'weekStartsOn',
								'value'  => Option::get( 'week-starts-on', 1 ),
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
							'options'     => App\Support\Timezones::all(),
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
					'type'   => 'group',
					'name'   => 'comments',
					'label'  => t( 'Post Comments' ),
					'fields' => [
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
							'attributes'  => [
								'u-prop' => 'comments',
							],
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
									'checked'     => Option::get( 'comments.require_name_email', true ),
								],
								'comments[registration]' => [
									'content'     => t( 'Users must be registered and logged in to comment' ),
									'icon'        => 'ph ph-browser',
									'description' => '',
									'checked'     => Option::get( 'comments.registration' ),
								],
								'comments[close_comments_for_old_posts]' => [
									'content'     => t( 'Automatically close comments on posts older than %s days', sprintf( '<span class="field--xs field--raised"><samp class="field-item"><input type="number" name="comments[close_comments_days_old]" value="%d" min="1"></samp></span>', max( 1, (int) Option::get( 'comments.close_comments_days_old', 14 ) ) ) ),
									'icon'        => 'ph ph-hourglass-medium',
									'description' => '',
									'checked'     => Option::get( 'comments.close_comments_for_old_posts' ),
								],
								'comments[thread_comments]' => [
									'content'     => t( 'Enable threaded (nested) comments %s levels deep', sprintf( '<span class="field--xs field--raised"><samp class="field-item"><input type="number" name="comments[thread_comments_depth]" value="%d" min="2" max="10"></samp></span>', max( 2, min( 10, (int) Option::get( 'comments.thread_comments_depth', 5 ) ) ) ) ),
									'icon'        => 'ph ph-stack',
									'description' => t( 'Replies stop at the last level: its comments have no Reply.' ),
									'checked'     => Option::get( 'comments.thread_comments', true ),
								],
							],
						],
					],
				],
				[
					'type'   => 'group',
					'name'   => 'comments',
					'label'  => t( 'Email Me Whenever' ),
					'fields' => [
						[
							'type'        => 'checkbox',
							'name'        => 'comments[notify_posts]',
							'error'       => 'comments.notify_posts',
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
								'u-prop' => 'comments.notifyPosts',
							],
							'options'     => [
								'comments[notify_posts]' => [
									'content'     => t( 'Anyone posts a comment' ),
									'icon'        => 'ph ph-chats',
									'description' => t( 'Individual posts may override these settings. Changes here will only be applied to new posts.' ),
									'checked'     => Option::get( 'comments.notify_posts', true ),
								],
								'comments[notify_moderation]' => [
									'content'     => t( 'A comment is held for moderation' ),
									'icon'        => 'ph ph-detective',
									'description' => '',
									'checked'     => Option::get( 'comments.notify_moderation', true ),
								],
							],
						],
					],
				],
				[
					'type'   => 'group',
					'name'   => 'appears',
					'label'  => t( 'Before a Comment Appears' ),
					'fields' => [
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
							'attributes'  => [
								'u-prop' => 'comments',
							],
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
					'type'   => 'group',
					'name'   => 'avatars',
					'label'  => t( 'Avatar Display' ),
					'fields' => [
						[
							'type'        => 'checkbox',
							'name'        => 'avatars[show]',
							'error'       => 'avatars.show',
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
								'u-prop' => 'avatars.show',
							],
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
								'value'  => 'mystery',
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
									'title'   => t( 'Generated', context: 'avatar' ),
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
					'type'   => 'group',
					'name'   => 'sign-in-limits',
					'label'  => t( 'Password Guessing' ),
					'fields' => [
						[
							'type'        => 'number',
							'name'        => 'security[attempts]',
							'error'       => 'security.attempts',
							'label'       => t( 'Wrong Passwords Before a Lockout' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Per browser that signed in before, or per login from unknown browsers; 0 turns the limit off.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (int) Option::get( 'security.attempts', 5 ),
								'min'   => 0,
							],
						],
						[
							'type'        => 'number',
							'name'        => 'security[ip_attempts]',
							'error'       => 'security.ip_attempts',
							'label'       => t( 'Wrong Passwords per IP' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'From unknown browsers, for any login: stops one password tried on many accounts; 0 turns it off.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (int) Option::get( 'security.ip_attempts', 50 ),
								'min'   => 0,
							],
						],
						[
							'type'        => 'number',
							'name'        => 'security[lockout]',
							'error'       => 'security.lockout',
							'label'       => t( 'First Lockout, Minutes' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Each next lockout is twice as long, up to a day.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (int) Option::get( 'security.lockout', 15 ),
								'min'   => 1,
							],
						],
						[
							'type'        => 'number',
							'name'        => 'security[log_days]',
							'error'       => 'security.log_days',
							'label'       => t( 'Keep the Security Log, Days' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Sign-ins and changes shown in the profiles; 0 keeps them forever.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (int) Option::get( 'security.log_days', 90 ),
								'min'   => 0,
							],
						],
						[
							'type'        => 'hidden',
							'name'        => 'security[breached]',
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
							'type'        => 'hidden',
							'name'        => 'security[email_link]',
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
					'type'   => 'group',
					'name'   => 'two-factor',
					'label'  => t( 'Two-Factor Authentication' ),
					'fields' => [
						[
							'type'        => 'hidden',
							'name'        => 'security[two_factor_roles][_]',
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
							'attributes'  => [
								'u-prop' => '',
							],
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
					'type'   => 'group',
					'name'   => 'oauth',
					'label'  => t( 'Sign-In Providers' ),
					'fields' => [
						[
							'type'        => 'text',
							'name'        => 'oauth[google][client_id]',
							'error'       => 'oauth.google.client_id',
							'label'       => t( 'Google Client ID' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Callback URL: :url', url( 'oauth/google/callback' ) ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'oauth.google.client_id' ),
							],
						],
						[
							'type'        => 'password',
							'name'        => 'oauth[google][client_secret]',
							'error'       => 'oauth.google.client_secret',
							'label'       => t( 'Google Client Secret' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'oauth.google.client_secret' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'autocomplete' => 'new-password',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'oauth[github][client_id]',
							'error'       => 'oauth.github.client_id',
							'label'       => t( 'GitHub Client ID' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Callback URL: :url', url( 'oauth/github/callback' ) ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'oauth.github.client_id' ),
							],
						],
						[
							'type'        => 'password',
							'name'        => 'oauth[github][client_secret]',
							'error'       => 'oauth.github.client_secret',
							'label'       => t( 'GitHub Client Secret' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'oauth.github.client_secret' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'autocomplete' => 'new-password',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'oauth[openid][label]',
							'error'       => 'oauth.openid.label',
							'label'       => t( 'OpenID Connect Provider Name' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Any OpenID Connect provider, e.g. GitLab or Keycloak. Callback URL: :url', url( 'oauth/openid/callback' ) ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'oauth.openid.label' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'oauth[openid][issuer]',
							'error'       => 'oauth.openid.issuer',
							'label'       => t( 'Issuer URL' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'e.g. https://gitlab.com' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'oauth.openid.issuer' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'oauth[openid][client_id]',
							'error'       => 'oauth.openid.client_id',
							'label'       => t( 'Client ID' ),
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
								'value' => (string) Option::get( 'oauth.openid.client_id' ),
							],
						],
						[
							'type'        => 'password',
							'name'        => 'oauth[openid][client_secret]',
							'error'       => 'oauth.openid.client_secret',
							'label'       => t( 'Client Secret' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'oauth.openid.client_secret' ),
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
			'name'    => 'mail',
			'type'    => 'tab',
			'label'   => t( 'Mail' ),
			'caption' => t( 'Outgoing Email' ),
			'icon'    => 'ph ph-mailbox',
			'fields'  => [
				[
					'type'   => 'group',
					'name'   => 'smtp',
					'label'  => t( 'SMTP Server' ),
					'fields' => [
						[
							'type'        => 'text',
							'name'        => 'mail[host]',
							'error'       => 'mail.host',
							'label'       => t( 'Server' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Without a server the mail goes through PHP mail() and often lands in spam.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'mail.host' ),
								'placeholder' => 'smtp.example.com',
							],
						],
						[
							'type'        => 'number',
							'name'        => 'mail[port]',
							'error'       => 'mail.port',
							'label'       => t( 'Port' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Usually 465 for SSL and 587 for STARTTLS.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (int) Option::get( 'mail.port', 465 ),
								'min'   => 1,
							],
						],
						[
							'type'        => 'select',
							'name'        => 'mail[encryption]',
							'error'       => 'mail.encryption',
							'label'       => t( 'Encryption' ),
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
								'value' => (string) Option::get( 'mail.encryption', 'ssl' ),
							],
							'options'     => [
								'ssl'  => 'SSL',
								'tls'  => 'STARTTLS',
								'none' => t( 'None' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'mail[username]',
							'error'       => 'mail.username',
							'label'       => t( 'Login' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Leave empty if the server needs no login.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'        => (string) Option::get( 'mail.username' ),
								'autocomplete' => 'off',
							],
						],
						[
							'type'        => 'password',
							'name'        => 'mail[password]',
							'error'       => 'mail.password',
							'label'       => t( 'Password' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'mail.password' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'autocomplete' => 'new-password',
							],
						],
						[
							'type'        => 'email',
							'name'        => 'mail[from]',
							'error'       => 'mail.from',
							'label'       => t( 'Sender Address' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'An address of the site domain, otherwise the mail lands in spam.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'mail.from' ),
								'placeholder' => 'no-reply@example.com',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'mail[from_name]',
							'error'       => 'mail.from_name',
							'label'       => t( 'Sender Name' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'The site name when empty.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'mail.from_name' ),
							],
						],
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
					'type'   => 'group',
					'name'   => 'dkim',
					'label'  => t( 'DKIM Signature' ),
					'fields' => [
						[
							'type'        => 'text',
							'name'        => 'mail[dkim][domain]',
							'error'       => 'mail.dkim.domain',
							'label'       => t( 'Domain' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'The site domain when empty.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'mail.dkim.domain' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'mail[dkim][selector]',
							'error'       => 'mail.dkim.selector',
							'label'       => t( 'Selector' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Name of the DNS record with the public key, e.g. "mail" for mail._domainkey.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => (string) Option::get( 'mail.dkim.selector' ),
							],
						],
						[
							'type'        => 'textarea',
							'name'        => 'mail[dkim][private]',
							'error'       => 'mail.dkim.private',
							'label'       => t( 'Private Key' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'mail.dkim.private', t( 'PEM, begins with -----BEGIN PRIVATE KEY-----.' ) ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'rows' => 4,
							],
						],
						[
							'type'        => 'password',
							'name'        => 'mail[dkim][passphrase]',
							'error'       => 'mail.dkim.passphrase',
							'label'       => t( 'Key Passphrase' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'mail.dkim.passphrase', t( 'Only for an encrypted key.' ) ),
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
			'name'    => 'ai',
			'type'    => 'tab',
			'label'   => t( 'AI' ),
			'caption' => t( 'Plugin Generation' ),
			'icon'    => 'ph ph-sparkle',
			'fields'  => [
				[
					'type'   => 'group',
					'name'   => 'ai-service',
					'label'  => t( 'AI Service' ),
					'fields' => [
						[
							'type'        => 'url',
							'name'        => 'ai[url]',
							'error'       => 'ai.url',
							'label'       => t( 'Address' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'Any OpenAI-compatible Chat Completions API: Google Gemini, OpenRouter, a local Ollama at http://localhost:11434/v1/. Empty turns the generation off.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'ai.url', App\Support\Ai::URL ),
								'placeholder' => App\Support\Ai::URL,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'ai[model]',
							'error'       => 'ai.model',
							'label'       => t( 'Model' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => t( 'e.g. gemini-flash-latest, or a model ID of OpenRouter ending in :free.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => (string) Option::get( 'ai.model', App\Support\Ai::MODEL ),
								'placeholder' => App\Support\Ai::MODEL,
							],
						],
						[
							'type'        => 'password',
							'name'        => 'ai[key]',
							'error'       => 'ai.key',
							'label'       => t( 'Service Key' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => $saved( 'ai.key', t( 'A local service needs none.' ) ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'autocomplete' => 'new-password',
							],
						],
						[
							'type'        => 'hidden',
							'name'        => 'ai[schemas]',
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
					'type'   => 'group',
					'name'   => 'roles',
					'label'  => t( 'Roles and Permissions' ),
					'fields' => [
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
											<div class="df fw fdc g-3 fs-13">
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