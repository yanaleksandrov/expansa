<?php

use App\Api\User\Confirmation;
use App\Api\User\Events;
use App\Api\User\Identities;
use App\Api\User\Passkey;
use App\Api\User\Sessions;
use App\Api\User\Tokens;
use App\Api\User\TwoFactor;
use App\Models\User;
use App\Support\Passwords;
use Expansa\Facades\Auth;
use Expansa\Facades\Form;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Facades\Role;
use Expansa\Facades\Safe;

$user = User::current();

/**
 * Profile page.
 *
 * @since 2025.1
 */
return Form::enqueue(
    'user-profile',
    [
        'class'           => 'tab',
        'u-data'          => 'tab',
		'@load'           => '$dirty.watch($el)',
	    '@submit.prevent' => '$ajax.post("user/update", "", () => $dirty.remove($el))',
    ],
    [
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
                <div class="dg g-1 p-7 sm:p-5 pb-4 sm:pb-4 bg-gray-lt">
                    <?php
                    echo view(
                        'form/image',
                        [
                            'type'        => 'image',
                            'name'        => 'avatar',
                            'label'       => t('Profile Settings'),
                            'class'       => '',
                            'label_class' => 'field-label fw-500 fs-18',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Click to upload your avatar'),
                            'tooltip'     => t('This is a tooltip'),
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'avatar',
                                'name'    => 'avatar',
                                '@change' => '[...$refs.uploader.files].map(file => $ajax.post("upload/media").then(response => files.unshift(response[0])))',
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
            'label'         => t('Overview'),
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
                    'label'         => t('Contact info'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-7 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'email',
                            'name'        => 'email',
                            'label'       => t('Your email'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '<i class="ph ph-at"></i>',
                            'after'       => '',
                            'instruction' => (string) $user->field->find('pending_email') !== ''
                                ? t('Waiting for confirmation: open the link sent to :email. Until then the current email stays.', (string) $user->field->find('pending_email'))
                                : t('Not displayed publicly. Used for account access and system notifications'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'email',
                                'value'          => $user->email ?? '',
                                'placeholder'    => t('e.g. user@gmail.com'),
                                'u-autocomplete' => '',
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'name',
                    'label'         => t('Name'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'type'        => 'text',
                            'name'        => 'login',
                            'label'       => t('Login'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '<i class="ph ph-user"></i>',
                            'after'       => '',
                            'instruction' => t('Can\'t be changed because it\'s used to sign in to your account'),
                            'tooltip'     => '',
                            'copy'        => 1,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'login',
                                'value'       => $user->login ?? '',
                                'placeholder' => t('e.g. admin'),
                                'required'    => true,
                                'readonly'    => true,
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'nicename',
                            'label'       => t('Nicename'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('This field is used as part of the profile page URL'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'nicename',
                                'value'       => $user->nicename ?? '',
                                'placeholder' => t('Username'),
                                'required'    => true,
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'firstname',
                            'label'       => t('First name'),
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
                                'u-prop' => 'firstname',
                                'value'       => $user->firstname ?? '',
                                'placeholder' => t('e.g. John'),
                                '@input'      => 'showname = `${firstname} ${lastname}`',
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'lastname',
                            'label'       => t('Last name'),
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
                                'u-prop' => 'lastname',
                                'value'       => $user->lastname ?? '',
                                'placeholder' => t('e.g. Doe'),
                                '@input'      => 'showname = `${firstname} ${lastname}`',
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'showname',
                            'label'       => t('Show name as'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '<i class="ph ph-identification-badge"></i>',
                            'after'       => '',
                            'instruction' => t('Your name may appear on the website wherever you contribute or are mentioned'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'showname',
                                'value' => $user->showname ?? '',
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'about-yourself',
                    'label'         => t('About yourself'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => 'dg ga-4 g-7 gtc-1',
                    'fields'        => [
                        [
                            'type'        => 'textarea',
                            'name'        => 'bio',
                            'label'       => t('Biographical info'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Share a little biographical information to fill out your profile. This may be shown publicly.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'bio',
                                'rows'        => count(explode("\n", $user->field->find('bio') ?? '')),
                                'value'       => $user->field->find('bio'),
                                'placeholder' => t('A few words about yourself'),
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'          => 'appearance',
            'type'          => 'tab',
            'label'         => t('Appearance'),
            'description'   => '',
            'icon'          => 'ph ph-paint-brush-broad',
            'class_button'  => '',
            'class_content' => 'p-7 sm:p-5',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'theme',
                    'label'         => t('Theme preferences'),
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
                            'instruction' => t('Choose how the dashboard looks to you. Select a single theme, or sync with your system to switch between light and dark themes automatically.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'format',
                                'value' => $user->field->find('format'),
                            ],
                            'options'     => [
                                'light' => [
                                    'content'     => t('Light mode'),
                                    'icon'        => 'ph ph-user-list',
                                    'description' => t('This theme will be active when your system is set to “light mode”'),
                                    'checked'     => $user->field->find('format') === 'light',
                                    'image'       => url('dashboard/assets/images/dashboard-light.svg'),
                                ],
                                'dark'  => [
                                    'content'     => t('Dark mode'),
                                    'icon'        => 'ph ph-police-car',
                                    'description' => t('This theme will be active when your system is set to “night mode”'),
                                    'checked'     => $user->field->find('format') === 'dark',
                                    'image'       => url('dashboard/assets/images/dashboard-dark.svg'),
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'toolbar',
                    'label'         => t('Toolbar'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'type'        => 'checkbox',
                            'name'        => 'toolbar',
                            'label'       => t('Show when viewing site'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('these settings can be changed for each user separately'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'toolbar',
                                'checked' => $user->field->find('toolbar'),
                            ],
                            'options'     => [],
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'translations',
                    'label'         => t('Translations'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'type'        => 'select',
                            'name'        => 'locale',
                            'label'       => t('Language'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('Your dashboard language'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'locale',
                                'u-select' => '',
                                'value'    => $user->locale ?? '',
                            ],
                            'options'     => I18n::languageOptions(),
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'          => 'password',
            'type'          => 'tab',
            'label'         => t('Security'),
            'caption'       => '',
            'icon'          => 'ph ph-password',
            'class_button'  => '',
            'class_content' => 'p-7 sm:p-5',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'confirmation',
                    'label'         => t('Confirm it is you'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'confirmation',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('Adding or removing sign-in methods, signing out of devices and changing the email need your current password or a passkey. It is asked once in :minutes minutes.', Confirmation::TTL / 60); ?></div>
                                    <?php if (Confirmation::isConfirmed($user)) : ?>
                                        <div class="df aic g-1 fs-13"><i class="ph ph-check-circle"></i> <?php echo t('Confirmed for this session.'); ?></div>
                                    <?php endif; ?>
                                    <div class="df aic fw g-2">
                                        <div class="field">
                                            <div class="field-item">
                                                <input type="password" id="confirm-password" u-prop="confirmPassword" autocomplete="current-password" placeholder="<?php echo t_attr('Current password'); ?>" @keydown.enter.prevent="$refs.confirm.click()">
                                            </div>
                                        </div>
                                        <button class="btn btn--outline" type="button" u-ref="confirm" :disabled="!confirmPassword" @click="$ajax.post('user/confirm', {password: confirmPassword})">
                                            <i class="ph ph-lock-key-open"></i> <?php echo t('Confirm'); ?>
                                        </button>
                                        <button class="btn btn--outline" type="button" hidden u-show="$passkey.available" @click="$ajax.post('user/confirm-passkey-options').then(({options}) => $passkey.get(options)).then(credential => credential && $ajax.post('user/confirm-passkey', {credential}))">
                                            <i class="ph ph-fingerprint"></i> <?php echo t('Confirm with a passkey'); ?>
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
                    'name'          => 'two-factor',
                    'label'         => t('Two-factor authentication'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'two-factor',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                ?>
                                <?php if (TwoFactor::isRequired($user) && ! TwoFactor::isEnabled($user)) : ?>
                                    <div class="df aic g-1 t-red fs-13 mb-2"><i class="ph ph-warning-circle"></i> <?php echo t('Your role requires two-factor authentication: set it up to use the dashboard.'); ?></div>
                                <?php endif; ?>
                                <div id="two-factor"><?php echo view('parts/two-factor', ['user' => $user]); ?></div>
                                <?php
                            },
                        ],
                    ],
                ],
                [
                    'type'          => 'group',
                    'name'          => 'passkeys',
                    'label'         => t('Passkeys'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'passkeys',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('Sign in with your fingerprint, face, screen lock or security key instead of a password. Passkeys stay on your devices and cannot be phished.'); ?></div>
                                    <div class="dg g-2" id="passkeys">
                                        <?php
                                        foreach (Passkey::all($user) as $passkey) {
                                            echo view('parts/passkey', ['passkey' => $passkey]);
                                        }
                                        ?>
                                    </div>
                                    <div hidden u-show="$passkey.available">
                                        <button class="btn btn--outline" type="button" @click="$ajax.post('user/passkey-create-options', {password: confirmPassword}).then(({options}) => $passkey.create(options)).then(credential => credential && $ajax.post('user/passkey-create', {credential, name: $passkey.device()}))">
                                            <i class="ph ph-plus"></i> <?php echo t('Add passkey'); ?>
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
                    'name'          => 'sessions',
                    'label'         => t('Devices'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'sessions',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                $sessions = Sessions::all($user);
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('Devices where you are signed in. Sign out of the ones you do not recognize and change the password.'); ?></div>
                                    <div class="dg g-2" id="sessions">
                                        <?php
                                        foreach ($sessions as $session) {
                                            echo view('parts/session', ['session' => $session]);
                                        }
                                        ?>
                                    </div>
                                    <?php if (count($sessions) > 1) : ?>
                                        <div data-session-other>
                                            <button class="btn btn--outline t-red" type="button" @click="$ajax.post('user/sessions-delete-others', {password: confirmPassword})">
                                                <i class="ph ph-sign-out"></i> <?php echo t('Sign out of all other devices'); ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
                ...(Auth::getProviders() === [] ? [] : [[
                    'type'          => 'group',
                    'name'          => 'identities',
                    'label'         => t('Connected accounts'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'identities',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                $errors = [
                                    'oauth-denied'  => t('Connecting was cancelled.'),
                                    'oauth-failed'  => t('Could not connect the account. Please try again.'),
                                    'oauth-linked'  => t('This account is already connected to another user.'),
                                    'oauth-limited' => t('Too many attempts. Try again in a few minutes.'),
                                ];
                                $error  = $errors[$_GET['error'] ?? ''] ?? '';
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('Sign in with an account of another service.'); ?></div>
                                    <?php if ($error !== '') : ?>
                                        <div class="df aic g-1 t-red fs-13"><i class="ph ph-warning-circle"></i> <?php echo $error; ?></div>
                                    <?php endif; ?>
                                    <div class="dg g-2" id="identities">
                                        <?php
                                        foreach (Identities::all($user) as $identity) {
                                            echo view('parts/identity', ['identity' => $identity]);
                                        }
                                        ?>
                                    </div>
                                    <div class="df aic fw g-2">
                                        <?php foreach (Auth::getProviders() as $provider) : ?>
                                            <button class="btn btn--outline" type="button" @click="$ajax.post('user/identity-connect', {provider: '<?php echo $provider; ?>', password: confirmPassword})">
                                                <i class="ph ph-<?php echo $provider; ?>-logo"></i> <?php echo Identities::label($provider); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ]]),
                [
                    'type'          => 'group',
                    'name'          => 'tokens',
                    'label'         => t('API tokens'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'tokens',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                $permissions = array_unique(array_merge([], ...array_map(fn (string $role) => Role::get($role)['permissions'] ?? [], $user->roles)));
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('Tokens let scripts and other services call the API as you: send the header Authorization: Bearer <token>. A token gets only the permissions you choose.'); ?></div>
                                    <div class="dg g-2" id="tokens"><?php echo view('parts/tokens', ['tokens' => Tokens::all($user)]); ?></div>
                                    <div id="token-created"></div>
                                    <div class="dg g-2 p-4 card card-border">
                                        <div class="df aic fw g-2">
                                            <div class="field">
                                                <div class="field-item">
                                                    <input type="text" id="token-name" placeholder="<?php echo t_attr('Token name, e.g. Deploy script'); ?>">
                                                </div>
                                            </div>
                                            <div class="field">
                                                <div class="field-item">
                                                    <select id="token-days">
                                                        <option value="30"><?php echo t('30 days'); ?></option>
                                                        <option value="90" selected><?php echo t('90 days'); ?></option>
                                                        <option value="365"><?php echo t('A year'); ?></option>
                                                        <option value="0"><?php echo t('No expiry'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="df fw g-3 fs-13">
                                            <?php foreach ($permissions as $permission) : ?>
                                                <label class="df aic g-1"><input type="checkbox" name="token-scope" value="<?php echo htmlspecialchars($permission); ?>"> <?php echo htmlspecialchars($permission); ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div>
                                            <button class="btn btn--outline" type="button" @click="$ajax.post('user/token-create', {name: document.getElementById('token-name').value, days: document.getElementById('token-days').value, scopes: [...document.querySelectorAll('[name=token-scope]:checked')].map(box => box.value).join(','), password: confirmPassword})">
                                                <i class="ph ph-plus"></i> <?php echo t('Create token'); ?>
                                            </button>
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
                    'name'          => 'activity',
                    'label'         => t('Recent activity'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'activity',
                            'type'     => 'custom',
                            'callback' => function () use ($user) {
                                $date = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
                                ?>
                                <div class="dg g-2 ga-4">
                                    <div><?php echo t('Sign-ins and security changes of your account. Something you did not do? Sign out of other devices and change the password.'); ?></div>
                                    <div class="dg g-1 fs-13">
                                        <?php foreach (Events::all($user) as $event) : ?>
                                            <div class="df aic g-2">
                                                <span><?php echo Events::label($event['event']); ?></span>
                                                <span class="t-muted"><?php echo htmlspecialchars($event['device'] . ' · ' . $event['ip'] . ' · ' . $date->format(strtotime($event['created_at']))); ?></span>
                                            </div>
                                        <?php endforeach; ?>
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
                    'label'         => t('Change password'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'type'        => 'password',
                            'name'        => 'password-new',
                            'label'       => t('New password'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('At least :count characters.', Passwords::MIN_LENGTH),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'passwordNew',
                                'autocomplete' => 'new-password',
                                'placeholder' => t('New password'),
                            ],
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
                        [
                            'type'        => 'password',
                            'name'        => 'password-old',
                            'label'       => t('Old password'),
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
                                'u-prop' => 'passwordOld',
                                'autocomplete' => 'current-password',
                                'u-autocomplete' => '',
                                'placeholder'    => t('Old password'),
                            ],
                            'switcher'    => 1,
                            'generator'   => 0,
                            'indicator'   => 0,
                            'characters'  => [],
                        ],
                        [
                            'type'        => 'submit',
                            'name'        => 'password-save',
                            'label'       => t('Update password'),
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
                                '@click'    => '$ajax.post("user/password-update", {current: passwordOld, password: passwordNew})',
                                'disabled'  => '',
                                ':disabled' => '!passwordOld || passwordNew.length < ' . Passwords::MIN_LENGTH,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'          => 'applications',
            'type'          => 'tab',
            'label'         => t('API keys'),
            'description'   => '',
            'icon'          => 'ph ph-key',
            'class_button'  => '',
            'class_content' => 'p-7 sm:p-5',
            'fields'        => [
                [
                    'type'          => 'group',
                    'name'          => 'auth',
                    'label'         => t('Authentication keys'),
                    'class'         => '',
                    'label_class'   => '',
                    'content_class' => '',
                    'fields'        => [
                        [
                            'name'     => 'title',
                            'type'     => 'custom',
                            'callback' => function () {
                                Hook::add('renderDashboardFooter', function () {
                                    echo view('dialogs/api-keys-manager');
                                }, 5);
                                ?>
                                <div class="dg ga-4">
                                    <div>
	                                    <p><?php echo t('Application passwords allow authentication via non-interactive systems, such as REST API, without providing your actual password. Application passwords can be easily revoked. They cannot be used for traditional logins to your website.'); ?></p>
	                                    <p>
		                                    <button class="btn btn--outline" type="button" @click="$dialog.open('tmpl-api-keys-manager', apiKeyManagerDialog)">
			                                    <i class="ph ph-plus"></i> <?php echo t('Add new key'); ?>
		                                    </button>
	                                    </p>
	                                    <template u-if="apiKeys.length">
		                                    <div>
			                                    <template u-for="(key, i) in apiKeys">
				                                    <div class="p-4 df fdr g-4 mb-2 card card-border">
					                                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 256 256">
						                                    <path d="M160 18a78 78 0 0 0-73.8 103.3l-58.4 58.5A6 6 0 0 0 26 184v40a6 6 0 0 0 6 6h40a6 6 0 0 0 6-6v-18h18a6 6 0 0 0 6-6v-18h18a6 6 0 0 0 4.2-1.8l10.5-10.4A78 78 0 1 0 160 18Zm0 144a65.6 65.6 0 0 1-24.4-4.7 6 6 0 0 0-6.7 1.3L117.5 170H96a6 6 0 0 0-6 6v18H72a6 6 0 0 0-6 6v18H38v-31.5L97.4 127a6 6 0 0 0 1.3-6.7A66 66 0 1 1 160 162Zm30-86a10 10 0 1 1-10-10 10 10 0 0 1 10 10Z"/>
					                                    </svg>
					                                    <div class="dg g-1">
						                                    <h6 class="fs-14" u-text="key.title"></h6>
						                                    <code class="fs-12 df aic g-3 bg-green-lt t-green">
							                                    <span class="badge badge--sm badge--green-lt"><?php echo t('Active'); ?></span> <span u-text="key.token"></span>
							                                    <i class="fs-14 ph ph-copy" title="<?php echo t('Copy'); ?>" @click="$copy(key.token)"></i>
						                                    </code>
						                                    <div class="fs-12 t-muted lh-xs"><?php echo t('Created at'); ?> <span u-text="key.createdAt"></span></div>
					                                    </div>
					                                    <div class="ml-auto">
						                                    <button class="btn btn--sm btn--outline" type="button" @click="$dialog.open('tmpl-api-keys-manager', apiKeyManagerDialog)">
							                                    <i class="ph ph-pen"></i> <?php echo t('Edit'); ?>
						                                    </button>
						                                    <button class="btn btn--sm btn--icon t-red" type="button" @click="$ajax.post('apikey/delete', key, e => e.end && apiKeys.splice(i, 1))">
							                                    <i class="ph ph-trash"></i>
						                                    </button>
					                                    </div>
				                                    </div>
			                                    </template>
			                                    <p class="df aic g-1 t-red fs-13">
				                                    <i class="ph ph-info"></i> <?php echo t('The Expansa support team will never ask you to share your secret keys.'); ?>
			                                    </p>
		                                    </div>
	                                    </template>
	                                    <template u-if="!apiKeys.length">
                                            <?php
                                            echo view(
                                                'global/state',
                                                [
                                                    'icon'        => 'ufo',
                                                    'class'       => 'dg jic m-auto t-center p-7',
                                                    'title'       => t('No API keys found'),
                                                    'description' => t('To begin, click "Add new key" to create your first API key.'),
                                                ]
                                            );
                                            ?>
	                                    </template>
                                    </div>
                                </div>
                                <?php
                            },
                            'attributes'  => [ 'u-prop' => 'title' ],
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
