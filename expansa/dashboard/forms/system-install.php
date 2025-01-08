<?php

use app\View;
use Expansa\I18n;
use Expansa\Url;

/**
 * Register form for first Expansa installation.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'system-install',
	[
		'class'           => 'dg g-2',
		'@submit.prevent' => '$ajax("system/install").then(response => installed = response)',
		'x-data'          => '{approved: {}, site: {}, db: {}, user: {}, installed: false}',
		'x-init'          => '$watch("installed", () => $wizard.goNext())'
	],
	[
		[
			'type'       => 'step',
			'attributes' => [
				'class'         => 'dg g-8 pt-8',
				'x-wizard:step' => 'site.name?.trim()',
			],
			'fields'     => [
				[
					'type'        => 'header',
					'label'       => I18n::_t( 'Welcome to Expansa!' ),
					'name'        => 'title',
					'class'       => 't-center',
					'instruction' => I18n::_t( 'This is installation wizard. Before start, you need to set some settings. Please fill the information about your website.' ),
				],
				[
					'name'  => 'website-data',
					'type'  => 'divider',
					'label' => I18n::_t( 'Website data' ),
				],
				[
					'type'        => 'text',
					'name'        => 'site[name]',
					'label'       => I18n::_t( 'Site name' ),
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
						'placeholder'    => I18n::_t( 'Example: My Blog' ),
						'required'       => true,
						'x-autocomplete' => '',
					],
				],
				[
					'type'        => 'text',
					'name'        => 'site[tagline]',
					'label'       => I18n::_t( 'Site tagline' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( "Don't worry, you can always change these settings later" ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder'    => I18n::_t( 'Example: Just another Expansa site' ),
						'x-autocomplete' => '',
					],
				],
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'           => 'dg g-8 pt-8',
				'x-cloak'         => true,
				'x-wizard:step'   => '[db.database, db.username, db.password, db.host, db.prefix].every(value => value !== undefined && value.trim())',
				'x-wizard:action' => 'approved = {}',
			],
			'fields'     => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'label'       => I18n::_t( 'Step 1: Database' ),
					'instruction' => I18n::_t( 'Information about connecting to the database. If you are not sure about it, contact your hosting provider.' ),
				],
				[
					'name'  => 'credits',
					'type'  => 'divider',
					'label' => I18n::_t( 'Database credits' ),
				],
				[
					'type'        => 'text',
					'name'        => 'db[database]',
					'label'       => I18n::_t( 'Database name' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Specify the name of the empty database' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder'    => I18n::_t( 'database_name' ),
						'required'       => true,
						'x-autocomplete' => '',
					],
				],
				[
					'type'        => 'text',
					'name'        => 'db[username]',
					'label'       => I18n::_t( 'MySQL database user name' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'User of the all privileges in the database' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder'    => I18n::_t( 'user_name' ),
						'required'       => true,
						'x-autocomplete' => '',
					],
				],
				[	'type'        => 'text',
					'name'        => 'db[password]',
					'label'       => I18n::_t( 'MySQL password' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Password for the specified user' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder'    => I18n::_t( 'Password' ),
						'required'       => true,
						'x-autocomplete' => '',
					],
				],
				[
					'type'          => 'group',
					'name'          => 'system',
					'label'         => '',
					'class'         => 'dg g-7 gtc-4 sm:gtc-1',
					'label_class'   => '',
					'content_class' => '',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'db[host]',
							'label'       => I18n::_t( 'Hostname' ),
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
								'value'          => 'localhost',
								'placeholder'    => I18n::_t( 'Hostname' ),
								'required'       => true,
								'x-autocomplete' => '',
							],
						],
						[
							'type'        => 'text',
							'name'        => 'db[prefix]',
							'label'       => I18n::_t( 'Prefix' ),
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
								'value'          => 'expansa_',
								'placeholder'    => I18n::_t( 'Prefix' ),
								'required'       => true,
								'x-autocomplete' => '',
							],
						],
					],
				],
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'           => 'dg g-8 pt-8',
				'x-wizard:step'   => 'Object.values(approved).every(Boolean) === true',
				'x-wizard:action' => '$ajax("system/test", db).then(response => approved = response)',
				'x-cloak'         => true,
			],
			'fields'     => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'label'       => I18n::_t( 'Step 2: System check' ),
					'instruction' => I18n::_t( 'This is an important step that will help make sure that your server is ready for installation and properly configured.' ),
				],
				[
					'name'  => 'website-data',
					'type'  => 'divider',
					'label' => I18n::_t( 'System check' ),
				],
				[
					'name'     => 'date-format',
					'type'     => 'custom',
					'callback' => function() {
						$checks = [
							'pdo'        => I18n::_t( 'PDO PHP Extension' ),
							'curl'       => I18n::_t( 'cURL PHP Extension' ),
							'mbstring'   => I18n::_t( 'Mbstring PHP Extension' ),
							'gd'         => I18n::_t( 'GD PHP Extension' ),
							'memory'     => I18n::_t( '128MB or more allocated memory' ),
							'php'        => I18n::_t( 'PHP version 8.1 or higher' ),
							'connection' => I18n::_t( 'Testing the database connection' ),
							'mysql'      => I18n::_t( 'MySQL version 5.6 or higher' ),
						];
						?>
						<ul class="dg g-1">
							<?php foreach ( $checks as $icon => $title ) : ?>
								<li class="df aic">
									<span class="badge badge--xl badge--round badge--icon" :class="approved.<?php echo $icon; ?> === undefined ? 'badge--load' : (approved.<?php echo $icon; ?> ? 't-green' : 't-red')">
										<i class="ph" :class="approved.<?php echo $icon; ?> ? 'ph-check' : 'ph-x'"></i>
									</span>
									<span class="ml-4"><?php echo $title; ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php
					},
				],
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'         => 'dg g-8 pt-8',
				'x-cloak'       => true,
				'x-wizard:step' => '[user.login, user.email, user.password].every(value => value !== undefined && value.trim())',
			],
			'fields'     => [
				[
					'name'        => 'title',
					'type'        => 'header',
					'label'       => I18n::_t( 'Step 3: Create account' ),
					'instruction' => I18n::_t( 'Almost everything is ready! The last step: add website owner information.' ),
				],
				[
					'name'  => 'user-credits',
					'type'  => 'divider',
					'label' => I18n::_t( 'Owner credits' ),
				],
				[
					'type'        => 'email',
					'name'        => 'user[email]',
					'label'       => I18n::_t( 'Your email address' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Double-check your email address before continuing' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder'    => I18n::_t( 'Enter email' ),
						'@change'        => "user.login = user.email.split('@')[0]",
						'required'       => true,
						'x-autocomplete' => '',
					],
				],
				[
					'type'        => 'text',
					'name'        => 'user[login]',
					'label'       => I18n::_t( 'Your login' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'Can use only alphanumeric characters, underscores, hyphens and @ symbol' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder'    => I18n::_t( 'Enter login' ),
						'required'       => true,
						'x-autocomplete' => '',
					],
				],
				[
					'type'        => 'password',
					'name'        => 'user[password]',
					'label'       => I18n::_t( 'Your password' ),
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
						'placeholder'    => I18n::_t( 'Password' ),
						'required'       => true,
						'x-autocomplete' => '',
					],
					// password
					'switcher'   => 1,
					'generator'  => 1,
					'indicator'  => 0,
					'characters' => [
						'lowercase' => 2,
						'uppercase' => 2,
						'special'   => 2,
						'length'    => 12,
						'digit'     => 2,
					],
				]
			],
		],
		[
			'type'       => 'step',
			'attributes' => [
				'class'   => 'dg g-8 pt-8',
				'x-cloak' => true,
			],
			'fields'     => [
				[
					'type'     => 'custom',
					'callback' => function() {
						View::print(
							EX_PATH . 'dashboard/views/global/state',
							[
								'icon'        => 'success',
								'title'       => I18n::_t( 'Woo-hoo, Expansa has been successfully installed!' ),
								'description' => I18n::_t( 'We hope the installation process was easy. Thank you, and enjoy.' ),
							]
						);
					},
				],
			],
		],
		[
			'type'     => 'custom',
			'callback' => function() {
				?>
				<div class="py-8 df jcsb g-2">
					<button type="button" class="btn btn--outline" x-show="$wizard.isNotLast()" :disabled="$wizard.cannotGoBack()" @click="$wizard.goBack()" disabled><?php I18n::t( 'Back' ); ?></button>
					<button type="button" class="btn btn--primary" x-show="$wizard.isNotLast() && !$wizard.isStep(3)" :disabled="$wizard.cannotGoNext()" @click="$wizard.goNext()" disabled><?php I18n::t( 'Continue' ); ?></button>
					<button type="submit" class="btn btn--primary" x-show="$wizard.isStep(3)" :disabled="!['login', 'email', 'password'].every(key => user[key].trim())" x-cloak disabled><?php I18n::t( 'Install Expansa' ); ?></button>
					<a href="<?php echo Url::site( '/dashboard/profile' ); ?>" class="btn btn--primary mx-auto" x-show="$wizard.isLast()" x-cloak><?php I18n::t( 'Go to dashboard' ); ?></a>
				</div>
				<?php
			},
		],
	]
);