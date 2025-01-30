<?php

return \Expansa\Facades\Form::enqueue(
    'system-install',
    [
        'class'           => 'dg g-2',
        '@submit.prevent' => '$ajax("system/install").then(response => installed = response)',
        'x-data'          => '{approved: {}, site: {}, db: {}, user: {}, installed: false}',
        'x-init'          => '$watch("installed", () => $wizard.goNext())',
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
                    'label'       => t('Welcome to Expansa!'),
                    'name'        => 'title',
                    'class'       => 't-center',
                    'instruction' => t('This is installation wizard. Before start, you need to set some settings. Please fill the information about your website.'),
                ],
                [
                    'name'  => 'website-data',
                    'type'  => 'divider',
                    'label' => t('Website data'),
                ],
                [
                    'type'        => 'text',
                    'name'        => 'site[name]',
                    'label'       => t('Site name'),
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
                        'placeholder'    => t('Example: My Blog'),
                        'required'       => true,
                        'x-autocomplete' => '',
                    ],
                ],
                [
                    'type'        => 'text',
                    'name'        => 'site[tagline]',
                    'label'       => t('Site tagline'),
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t("Don't worry, you can always change these settings later"),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder'    => t('Example: Just another Expansa site'),
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
                    'label'       => t('Step 1: Database'),
                    'instruction' => t('Information about connecting to the database. If you are not sure about it, contact your hosting provider.'),
                ],
                [
                    'name'  => 'credits',
                    'type'  => 'divider',
                    'label' => t('Database credits'),
                ],
                [
                    'type'        => 'text',
                    'name'        => 'db[database]',
                    'label'       => t('Database name'),
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t('Specify the name of the empty database'),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder'    => t('database_name'),
                        'required'       => true,
                        'x-autocomplete' => '',
                    ],
                ],
                [
                    'type'        => 'text',
                    'name'        => 'db[username]',
                    'label'       => t('MySQL database user name'),
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t('User of the all privileges in the database'),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder'    => t('user_name'),
                        'required'       => true,
                        'x-autocomplete' => '',
                    ],
                ],
                [
                'type'        => 'text',
                    'name'        => 'db[password]',
                    'label'       => t('MySQL password'),
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t('Password for the specified user'),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder'    => t('Password'),
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
                            'label'       => t('Hostname'),
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
                                'placeholder'    => t('Hostname'),
                                'required'       => true,
                                'x-autocomplete' => '',
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'db[prefix]',
                            'label'       => t('Prefix'),
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
                                'placeholder'    => t('Prefix'),
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
                    'label'       => t('Step 2: System check'),
                    'instruction' => t('This is an important step that will help make sure that your server is ready for installation and properly configured.'),
                ],
                [
                    'name'  => 'website-data',
                    'type'  => 'divider',
                    'label' => t('System check'),
                ],
                [
                    'name' => 'checker',
                    'type' => 'checker',
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
                    'label'       => t('Step 3: Create account'),
                    'instruction' => t('Almost everything is ready! The last step: add website owner information.'),
                ],
                [
                    'name'  => 'user-credits',
                    'type'  => 'divider',
                    'label' => t('Owner credits'),
                ],
                [
                    'type'        => 'email',
                    'name'        => 'user[email]',
                    'label'       => t('Your email address'),
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t('Double-check your email address before continuing'),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder'    => t('Enter email'),
                        '@change'        => "user.login = user.email.split('@')[0]",
                        'required'       => true,
                        'x-autocomplete' => '',
                    ],
                ],
                [
                    'type'        => 'text',
                    'name'        => 'user[login]',
                    'label'       => t('Your login'),
                    'class'       => '',
                    'label_class' => '',
                    'reset'       => 0,
                    'before'      => '',
                    'after'       => '',
                    'instruction' => t('Can use only alphanumeric characters, underscores, hyphens and @ symbol'),
                    'tooltip'     => '',
                    'copy'        => 0,
                    'validator'   => '',
                    'conditions'  => [],
                    'attributes'  => [
                        'placeholder'    => t('Enter login'),
                        'required'       => true,
                        'x-autocomplete' => '',
                    ],
                ],
                [
                    'type'        => 'password',
                    'name'        => 'user[password]',
                    'label'       => t('Your password'),
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
                        'placeholder'    => t('Password'),
                        'required'       => true,
                        'x-autocomplete' => '',
                    ],
                    // password
                    'switcher'    => 1,
                    'generator'   => 1,
                    'indicator'   => 0,
                    'characters'  => [
                        'lowercase' => 2,
                        'uppercase' => 2,
                        'special'   => 2,
                        'length'    => 12,
                        'digit'     => 2,
                    ],
                ],
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
                    'callback' => view('global/state', [
                        'icon'        => 'success',
                        'title'       => t('Woo-hoo, Expansa has been successfully installed!'),
                        'description' => t('We hope the installation process was easy. Thank you, and enjoy.'),
                    ])
                ],
            ],
        ],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <div class="py-8 df jcsb g-2">
                    <button type="button" class="btn btn--outline" x-show="$wizard.isNotLast()" :disabled="$wizard.cannotGoBack()" @click="$wizard.goBack()" disabled>
                        <?php echo t('Back'); ?>
                    </button>
                    <button type="button" class="btn btn--primary" x-show="$wizard.isNotLast() && !$wizard.isStep(3)" :disabled="$wizard.cannotGoNext()" @click="$wizard.goNext()" disabled>
                        <?php echo t('Continue'); ?>
                    </button>
                    <button type="submit" class="btn btn--primary" x-show="$wizard.isStep(3)" :disabled="!['login', 'email', 'password'].every(key => user[key].trim())" x-cloak disabled>
                        <?php echo t('Install Expansa'); ?>
                    </button>
                    <a href="<?php echo url('/dashboard/profile'); ?>" class="btn btn--primary mx-auto" x-show="$wizard.isLast()" x-cloak><?php echo t('Go to dashboard'); ?></a>
                </div>
                <?php
            },
        ],
    ]
);