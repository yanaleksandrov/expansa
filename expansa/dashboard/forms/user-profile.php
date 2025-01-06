<?php

use Expansa\Db;
use Expansa\I18n;
use Expansa\Url;
use Expansa\Safe;
use Expansa\View;
use Expansa\User;
use Expansa\Hook;
use Expansa\Field;

$user  = User::current();
$field = new Field( $user );

/**
 * Profile page.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'user-profile',
	[
		'class'   => 'tab',
		'x-data'  => sprintf( "tab('%s')", Safe::prop( $_GET['tab'] ?? 'profile' ) ),
		'@change' => '$ajax("user/update")',
	],
	[
		[
			'type'     => 'custom',
			'callback' => function () {
				?>
				<div class="dg g-1 p-7 sm:p-5 pb-4 sm:pb-4 bg-gray-lt">
					<?php
					View::print(
						'views/form/image',
						[
							'type'        => 'image',
							'name'        => 'avatar',
							'label'       => I18n::_t( 'Profile Settings' ),
							'class'       => '',
							'label_class' => 'field-label fw-500 fs-18',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'Click to upload your avatar' ),
							'tooltip'     => I18n::_t( 'This is tooltip' ),
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'name'    => 'avatar',
								'@change' => '[...$refs.uploader.files].map(file => $ajax("upload/media").then(response => files.unshift(response[0])))',
							],
						]
					);
					?>
				</div>
				<?php
			},
		],
		[
			'type'          => 'tab',
			'label'         => I18n::_t( 'Overview' ),
			'name'          => 'profile',
			'caption'       => '',
			'description'   => '',
			'icon'          => 'ph ph-user',
			'class_menu'    => 'bg-gray-lt',
			'class_button'  => 'ml-7 sm:ml-5',
			'class_content' => 'p-7 sm:p-5',
			'fields'        => [
				[
					'type'          => 'group',
					'name'          => 'contacts',
					'label'         => I18n::_t( 'Contact info' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'email',
							'name'        => 'email',
							'label'       => I18n::_t( 'Your email' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-at"></i>',
							'after'       => '',
							'instruction' => I18n::_t( 'Is not displayed anywhere. It is used to work with the account and system notifications' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'          => $user->email ?? '',
								'placeholder'    => I18n::_t( 'e.g. user@gmail.com' ),
								'x-autocomplete' => '',
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'name',
					'label'         => I18n::_t( 'Name' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'login',
							'label'       => I18n::_t( 'Login' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-user"></i>',
							'after'       => '',
							'instruction' => I18n::_t( 'Cannot be changed because used to log in to your account' ),
							'tooltip'     => '',
							'copy'        => 1,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => $user->login ?? '',
								'placeholder' => I18n::_t( 'e.g. admin' ),
								'required'    => true,
								'readonly'    => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'nicename',
							'label'       => I18n::_t( 'Nicename' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'This field is used as part of the profile page URL' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'       => $user->nicename ?? '',
								'placeholder' => I18n::_t( 'Username' ),
								'required'    => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'firstname',
							'label'       => I18n::_t( 'First name' ),
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
								'value'       => $user->firstname ?? '',
								'placeholder' => I18n::_t( 'e.g. John' ),
								'@input'      => 'showname = `${firstname} ${lastname}`',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'lastname',
							'label'       => I18n::_t( 'Last name' ),
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
								'value'       => $user->lastname ?? '',
								'placeholder' => I18n::_t( 'e.g. Doe' ),
								'@input'      => 'showname = `${firstname} ${lastname}`',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'showname',
							'label'       => I18n::_t( 'Show name as' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '<i class="ph ph-identification-badge"></i>',
							'after'       => '',
							'instruction' => I18n::_t( 'Your name may appear around website where you contribute or are mentioned' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => $user->showname ?? '',
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'about-yourself',
					'label'         => I18n::_t( 'About yourself' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'textarea',
							'name'        => 'bio',
							'label'       => I18n::_t( 'Biographical info' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'Share a little biographical information to fill out your profile. This may be shown publicly.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'rows'        => count( explode("\n", $field->get( 'bio' ) ?? '' ) ),
								'value'       => $field->get( 'bio' ),
								'placeholder' => I18n::_t( 'A few words about yourself' ),
							],
						],
					],
				],
			],
		],
		[
			'name'          => 'appearance',
			'type'          => 'tab',
			'label'         => I18n::_t( 'Appearance' ),
			'description'   => '',
			'icon'          => 'ph ph-paint-brush-broad',
			'class_button'  => '',
			'class_content' => 'p-7 sm:p-5',
			'fields'        => [
				[
					'type'          => 'group',
					'name'          => 'theme',
					'label'         => I18n::_t( 'Theme preferences' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-7 gtc-1',
					'fields'        => [
						[
							'type'        => 'radio',
							'name'        => 'format',
							'label'       => '',
							'class'       => 'field field--grid',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'Choose how dashboard looks to you. Select a single theme, or sync with your system and automatically switch between day and night themes.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => $field->get( 'format' ),
							],
							'options'     => [
								'light' => [
									'content'     => I18n::_t( 'Light mode' ),
									'icon'        => 'ph ph-user-list',
									'description' => I18n::_t( 'This theme will be active when your system is set to “light mode”' ),
									'checked'     => $field->get( 'format' ) === 'light',
									'image'       => Url::site( 'dashboard/assets/images/dashboard-light.svg' ),
								],
								'dark'  => [
									'content'     => I18n::_t( 'Dark mode' ),
									'icon'        => 'ph ph-police-car',
									'description' => I18n::_t( 'This theme will be active when your system is set to “night mode”' ),
									'checked'     => $field->get( 'format' ) === 'dark',
									'image'       => Url::site( 'dashboard/assets/images/dashboard-dark.svg' ),
								],
							],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'toolbar',
					'label'         => I18n::_t( 'Toolbar' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'checkbox',
							'name'        => 'toolbar',
							'label'       => I18n::_t( 'Show when viewing site' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'these settings can be changed for each user separately' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'checked' => $field->get( 'toolbar' ),
							],
							'options'     => [],
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'translations',
					'label'         => I18n::_t( 'Translations' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'select',
							'name'        => 'locale',
							'label'       => I18n::_t( 'Language' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( 'Language for your dashboard panel' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'x-select' => '{"showSearch": 1}',
								'value'    => $user->locale ?? '',
							],
							'options'     => I18n::getLanguagesOptions(),
						],
					],
				],
			],
		],
		[
			'name'          => 'password',
			'type'          => 'tab',
			'label'         => I18n::_t( 'Security' ),
			'caption'       => '',
			'icon'          => 'ph ph-password',
			'class_button'  => '',
			'class_content' => 'p-7 sm:p-5',
			'fields'        => [
				[
					'type'          => 'group',
					'name'          => 'sessions',
					'label'         => I18n::_t( 'Web sessions' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'name'     => 'title',
							'type'     => 'custom',
							'callback' => function () {
								?>
								<div class="dg g-2 ga-4">
									<div>This is a list of devices that have logged into your account. Revoke any sessions that you do not recognize.</div>
									<div class="p-4 df fdr g-4 card card-border">
										<div class="avatar">
											<i class="badge"></i>
											<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 256 256">
												<path d="M224 74h-18V64a22 22 0 0 0-22-22H40a22 22 0 0 0-22 22v96a22 22 0 0 0 22 22h114v10a22 22 0 0 0 22 22h48a22 22 0 0 0 22-22V96a22 22 0 0 0-22-22ZM40 170a10 10 0 0 1-10-10V64a10 10 0 0 1 10-10h144a10 10 0 0 1 10 10v10h-18a22 22 0 0 0-22 22v74Zm194 22a10 10 0 0 1-10 10h-48a10 10 0 0 1-10-10V96a10 10 0 0 1 10-10h48a10 10 0 0 1 10 10Zm-100 16a6 6 0 0 1-6 6H88a6 6 0 0 1 0-12h40a6 6 0 0 1 6 6Zm80-96a6 6 0 0 1-6 6h-16a6 6 0 0 1 0-12h16a6 6 0 0 1 6 6Z"/>
											</svg>
										</div>
										<div class="dg g-1">
											<h6 class="fs-15">Turkey, Antalya 46.197.118.72</h6>
											<code class="fs-12">Microsoft Edge on Windows</code>
											<div class="fs-12 t-muted lh-xs">Your current session</div>
										</div>
										<div class="ml-auto">
											<button class="btn btn--outline" type="button">Delete</button>
										</div>
									</div>
									<div class="p-4 df fdr g-4 card card-border">
										<div class="avatar">
											<i class="badge badge--green"></i>
											<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 256 256">
												<path d="M176 18H80a22 22 0 0 0-22 22v176a22 22 0 0 0 22 22h96a22 22 0 0 0 22-22V40a22 22 0 0 0-22-22Zm10 198a10 10 0 0 1-10 10H80a10 10 0 0 1-10-10V40a10 10 0 0 1 10-10h96a10 10 0 0 1 10 10ZM138 60a10 10 0 1 1-10-10 10 10 0 0 1 10 10Z"/>
											</svg>
										</div>
										<div class="dg g-1">
											<h6 class="fs-15">Germany, Berlin 26.144.105.72</h6>
											<code class="fs-12">Chromium on Linux</code>
											<div class="fs-12 t-muted lh-xs">Your current session</div>
										</div>
										<div class="ml-auto">
											<button class="btn btn--outline" type="button">Delete</button>
										</div>
									</div>
								</div>
								<?php
							},
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'passwords',
					'label'         => I18n::_t( 'Change password' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'password',
							'name'        => 'password-new',
							'label'       => I18n::_t( 'New password' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => I18n::_t( "Make sure it's at least 15 characters OR at least 12 characters including a number and a lowercase letter." ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'placeholder' => I18n::_t( 'New password' ),
							],
							'switcher'    => 1,
							'generator'   => 1,
							'indicator'   => 0,
							'characters' => [
								'lowercase' => 2,
								'uppercase' => 2,
								'special'   => 2,
								'length'    => 12,
								'digit'     => 2,
							],
						],
						[
							'type'        => 'password',
							'name'        => 'password-old',
							'label'       => I18n::_t( 'Old password' ),
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
								'x-autocomplete' => '',
								'placeholder'    => I18n::_t( 'Old password' ),
							],
							'switcher'    => 1,
							'generator'   => 0,
							'indicator'   => 0,
							'characters' => [],
						],
						[
							'type'        => 'submit',
							'name'        => 'password-save',
							'label'       => I18n::_t( 'Update password' ),
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
								'type'      => 'button',
								'class'     => 'btn btn--primary btn--full',
								'@click'    => '$ajax("user/password-update", $data)',
								'disabled'  => '',
								':disabled' => '!(passwordNew && passwordOld)',
							],
						],
					],
				],
			],
		],
		[
			'name'          => 'applications',
			'type'          => 'tab',
			'label'         => I18n::_t( 'API keys' ),
			'description'   => '',
			'icon'          => 'ph ph-key',
			'class_button'  => '',
			'class_content' => 'p-7 sm:p-5',
			'fields'        => [
				[
					'type'          => 'group',
					'name'          => 'auth',
					'label'         => I18n::_t( 'Authentication keys' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'name'     => 'title',
							'type'     => 'custom',
							'callback' => function () {
								Hook::add( 'expansa_dashboard_footer', function() {
									View::print( 'views/dialogs/api-keys-manager' );
								} );
								?>
								<div class="dg g-2 ga-4">
									<p><?php I18n::t( 'Application passwords allow authentication via non-interactive systems, such as REST API, without providing your actual password. Application passwords can be easily revoked. They cannot be used for traditional logins to your website.' ); ?></p>
									<div>
										<button class="btn btn--outline" type="button" @click="$dialog.open('tmpl-api-keys-manager', apiKeyManagerDialog)">
											<i class="ph ph-plus"></i> Add new key
										</button>
									</div>
									<div class="p-4 df fdr g-4 card card-border">
										<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 256 256">
											<path d="M160 18a78 78 0 0 0-73.8 103.3l-58.4 58.5A6 6 0 0 0 26 184v40a6 6 0 0 0 6 6h40a6 6 0 0 0 6-6v-18h18a6 6 0 0 0 6-6v-18h18a6 6 0 0 0 4.2-1.8l10.5-10.4A78 78 0 1 0 160 18Zm0 144a65.6 65.6 0 0 1-24.4-4.7 6 6 0 0 0-6.7 1.3L117.5 170H96a6 6 0 0 0-6 6v18H72a6 6 0 0 0-6 6v18H38v-31.5L97.4 127a6 6 0 0 0 1.3-6.7A66 66 0 1 1 160 162Zm30-86a10 10 0 1 1-10-10 10 10 0 0 1 10 10Z"/>
										</svg>
										<div class="dg g-1">
											<h6 class="fs-15">Amplication</h6>
											<code class="fs-12 bg-green-lt t-green">
												<span class="badge badge--sm badge--green-lt">Active</span> SHA256:Ai2xqyVBORX9PJJigJxfrdzXfKPajJHZMYw3+dOo+nw
												<i class="ph ph-copy" title="<?php I18n::t_attr( 'Copy' ); ?>" @click="$copy()"></i>
											</code>
											<div class="fs-12 t-muted lh-xs">Added on Nov 15, 2022</div>
										</div>
										<div class="ml-auto">
											<button class="btn btn--outline" type="button"><i class="ph ph-trash-simple"></i> Delete</button>
										</div>
									</div>
									inspiration: https://dribbble.com/shots/24532847--API-keys
									<div class="df aic g-1 t-red fs-13"><i class="ph ph-info"></i> Expansa CMS support will never ask you to share your secret keys.</div>
								</div>
								<?php
							},
						],
					],
				],
			],
		],
	]
);