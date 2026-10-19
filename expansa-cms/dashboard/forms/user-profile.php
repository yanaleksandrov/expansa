<?php

use App\Api\User\Admin;
use App\Api\User\Confirmation;
use App\Api\User\Events;
use App\Api\User\Identities;
use App\Api\User\Passkey;
use App\Api\User\Sessions;
use App\Api\User\Tokens;
use App\Api\User\TwoFactor;
use App\Dashboard\Pages\Profile;
use App\Models\Option;
use App\Models\User;
use App\Support\Passwords;
use Expansa\Facades\Access;
use Expansa\Facades\Auth;
use Expansa\Builders\Form;
use Expansa\Facades\I18n;
use Expansa\Facades\Role;

$isNew  = isset($_GET['new']);
$user   = Profile::user(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0, $isNew);
$isSelf = ! $isNew && $user->id === User::current()?->id;

// the role is given by those who manage the users; their own one only by an owner, see Admin::refuseRole()
$roles     = Admin::assignableRoles(User::current());
$canManage = Access::allows(User::current(), 'users_edit');
$roleGroup = $isNew || ! $canManage ? [] : [
    [
        'type'   => 'group',
        'name'   => 'role',
        'label'  => t('Role'),
        'fields' => [
            [
                'type'        => 'select',
                'name'        => 'role',
                'error'       => 'role',
                'label'       => t('Role'),
                'class'       => '',
                'label_class' => '',
                'reset'       => 0,
                'before'      => '',
                'after'       => '',
                'instruction' => $isSelf && ! User::current()->hasRole(User::ROLE_OWNER)
                    ? t('Only the owner can change your role.')
                    : t('What the user can do on the site, see the Roles tab of the settings.'),
                'tooltip'     => '',
                'copy'        => 0,
                'validator'   => '',
                'conditions'  => [],
                'attributes'  => [
                    'value'    => $user->roles[0] ?? '',
                    'disabled' => $isSelf && ! User::current()->hasRole(User::ROLE_OWNER),
                ],
                // a role the administrator can't give stays shown on the account that has it
                'options'     => $roles + array_map(static fn (string $role) => Role::get($role)['name'] ?? $role, array_combine($user->roles, $user->roles)),
            ],
        ],
    ],
];

/**
 * Profile page: the account of the current user, or of another one by `id` for an administrator,
 * or a new account by `new`.
 *
 * @since 2027.1
 */
$uid = Form::enqueue(
    'user-profile',
    [
        'class'           => 'tab',
        'u-data'          => 'tab as tab',
		'@load'           => '$dirty.watch($el)',
	    '@submit.prevent' => '$ajax.post("user/update", "", () => $dirty.remove($el))',
    ],
    [
        [
            'type'        => 'tab',
            'label'       => t('Overview'),
            'name'        => 'profile',
            'caption'     => '',
            'description' => '',
            'icon'        => 'ph ph-user',
            'fields'      => [
	            [
		            'type'        => 'image',
		            'name'        => 'avatar',
		            'error'       => 'avatar',
		            'label'       => t('Profile Settings'),
		            'class'       => '',
		            'label_class' => 'field-label fw-500 fs-18',
		            'reset'       => 0,
		            'before'      => '',
		            'after'       => '',
		            'instruction' => $isSelf
		                ? t('Click to upload your avatar.')
		                : t('Click to upload the avatar of the user.'),
		            'tooltip'     => '',
		            'copy'        => 0,
		            'validator'   => '',
		            'conditions'  => [],
		            'attributes'  => [
			            'u-prop'  => 'avatar',
			            'name'    => 'avatar',
			            '@change' => '[...$refs.uploader.files].map(file => $ajax.post("upload/media").then(response => files.unshift(response[0])))',
		            ],
	            ],
                [
                    'type'   => 'group',
                    'name'   => 'contacts',
                    'label'  => t('Contact Info'),
                    'fields' => [
                        [
                            'type'        => 'email',
                            'name'        => 'email',
                            'error'       => 'email',
                            'label'       => $isSelf
                                ? t('Your email')
                                : t('Email of the user'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '<i class="ph ph-at"></i>',
                            'after'       => '',
                            'instruction' => (string) $user->field->find('pending_email') !== ''
                                ? t('Waiting for confirmation: open the link sent to :email. Until then the current email stays.', (string) $user->field->find('pending_email'))
                                : t('Not displayed publicly. Used for account access and system notifications.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'         => 'email',
                                'value'          => $user->email ?? '',
                                'placeholder'    => t('e.g. user@gmail.com'),
                                'u-autocomplete' => '',
                                'required'       => $isNew,
                                '@change'        => $isNew ? "login ||= email.split('@')[0]" : '',
                            ],
                        ],
                    ],
                ],
                ...$roleGroup,
                [
                    'type'    => 'group',
                    'name'    => 'name',
                    'label'   => t('Name', context: 'person'),
                    'columns' => 2,
                    'fields'  => [
                        [
                            'type'        => 'text',
                            'name'        => 'login',
                            'error'       => 'login',
                            'label'       => t('Login'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '<i class="ph ph-user"></i>',
                            'after'       => '',
                            'instruction' => match (true) {
                                $isNew  => t('Can\'t be changed later: it is used to sign in to the account.'),
                                $isSelf => t('Can\'t be changed because it\'s used to sign in to your account.'),
                                default => t('Can\'t be changed because it\'s used to sign in to the account.'),
                            },
                            'tooltip'     => '',
                            'copy'        => 1,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'      => 'login',
                                'value'       => $user->login ?? '',
                                'placeholder' => t('e.g. admin'),
                                'required'    => true,
                                'readonly'    => ! $isNew,
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'nicename',
                            'error'       => 'nicename',
                            'label'       => t('Nicename'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('This field is used as part of the profile page URL.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'      => 'nicename',
                                'value'       => $user->nicename ?? '',
                                'placeholder' => t('Username'),
                                'required'    => ! $isNew,
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'firstname',
                            'error'       => 'firstname',
                            'label'       => t('First Name'),
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
                                'u-prop'      => 'firstname',
                                'value'       => $user->firstname ?? '',
                                'placeholder' => t('e.g. John'),
                                '@input'      => 'showname = `${firstname} ${lastname}`',
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'lastname',
                            'error'       => 'lastname',
                            'label'       => t('Last Name'),
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
                                'u-prop'      => 'lastname',
                                'value'       => $user->lastname ?? '',
                                'placeholder' => t('e.g. Doe'),
                                '@input'      => 'showname = `${firstname} ${lastname}`',
                            ],
                        ],
                        [
                            'type'        => 'text',
                            'name'        => 'showname',
                            'error'       => 'showname',
                            'label'       => t('Show Name As'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '<i class="ph ph-identification-badge"></i>',
                            'after'       => '',
                            'instruction' => $isSelf
                                ? t('Your name may appear on the website wherever you contribute or are mentioned.')
                                : t('The name may appear on the website wherever the user contributes or is mentioned.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'showname',
                                'value'  => $user->showname ?? '',
                            ],
                        ],
                    ],
                ],
                [
                    'type'   => 'group',
                    'name'   => 'about-yourself',
                    'label'  => $isSelf
                        ? t('About Yourself')
                        : t('About the User'),
                    'fields' => [
                        [
                            'type'        => 'textarea',
                            'name'        => 'bio',
                            'error'       => 'bio',
                            'label'       => t('Biographical Info'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => $isSelf
                                ? t('Share a little biographical information to fill out your profile. This may be shown publicly.')
                                : t('Biographical information of the user. This may be shown publicly.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'      => 'bio',
                                'rows'        => 8,
                                'value'       => $user->field->find('bio'),
                                'placeholder' => $isSelf
                                    ? t('A few words about yourself')
                                    : t('A few words about the user'),
                            ],
                        ],
                    ],
                ],
            ],
        ],
        [
            'name'        => 'appearance',
            'type'        => 'tab',
            'label'       => t('Appearance'),
            'description' => '',
            'icon'        => 'ph ph-paint-brush-broad',
            'fields'      => [
                [
                    'type'   => 'group',
                    'name'   => 'theme',
                    'label'  => t('Theme Preferences'),
                    'fields' => [
                        [
                            'type'        => 'radio',
                            'name'        => 'format',
                            'error'       => 'format',
                            'label'       => '',
                            'class'       => 'field field--ui',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => $isSelf
                                ? t('Choose how the dashboard looks to you. Select a single theme, or sync with your system to switch between light and dark themes automatically.')
                                : t('Choose how the dashboard looks to the user. Select a single theme, or sync with their system to switch between light and dark themes automatically.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop' => 'format',
                                'value'  => $user->field->find('format'),
                            ],
                            'options'     => [
                                'light' => [
                                    'content'     => t('Light Mode'),
                                    'icon'        => 'ph ph-sun',
                                    'description' => $isSelf
                                        ? t('Your theme will follow your system’s light mode settings.')
                                        : t('The theme will follow the system’s light mode settings.'),
                                    'checked'     => $user->field->find('format') === 'light',
                                ],
                                'dark'  => [
                                    'content'     => t('Dark Mode'),
                                    'icon'        => 'ph ph-cloud',
                                    'description' => $isSelf
                                        ? t('Your theme will follow your system’s dark mode settings.')
                                        : t('The theme will follow the system’s dark mode settings.'),
                                    'checked'     => $user->field->find('format') === 'dark',
                                ],
	                            'auto'  => [
		                            'content'     => t('Auto Mode'),
		                            'icon'        => 'ph ph-cloud-sun',
		                            'description' => $isSelf
		                                ? t('Your theme will automatically adjust to match your system’s appearance settings.')
		                                : t('The theme will automatically adjust to match the system’s appearance settings.'),
		                            'checked'     => $user->field->find('format') === 'dark',
	                            ],
                            ],
                        ],
                    ],
                ],
                [
                    'type'    => 'group',
                    'name'    => 'toolbar',
                    'label'   => t('Toolbar'),
                    'columns' => 2,
                    'fields'  => [
                        [
                            'type'        => 'checkbox',
                            'name'        => 'toolbar',
                            'error'       => 'toolbar',
                            'label'       => t('Show When Viewing Site'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => t('These settings can be changed for each user separately.'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'  => 'toolbar',
                                'checked' => $user->field->find('toolbar'),
                            ],
                            'options'     => [],
                        ],
                    ],
                ],
                [
                    'type'    => 'group',
                    'name'    => 'translations',
                    'label'   => t('Translations'),
                    'columns' => 2,
                    'fields'  => [
                        [
                            'type'        => 'select',
                            'name'        => 'locale',
                            'error'       => 'locale',
                            'label'       => t('Language'),
                            'class'       => '',
                            'label_class' => '',
                            'reset'       => 0,
                            'before'      => '',
                            'after'       => '',
                            'instruction' => $isSelf
                                ? t('Your dashboard language')
                                : t('Dashboard language of the user'),
                            'tooltip'     => '',
                            'copy'        => 0,
                            'validator'   => '',
                            'conditions'  => [],
                            'attributes'  => [
                                'u-prop'   => 'locale',
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
            'name'    => 'password',
            'type'    => 'tab',
            'label'   => t('Security'),
            'caption' => '',
            'icon'    => 'ph ph-password',
            'fields'  => [
                [
                    'type'    => 'group',
                    'name'    => 'confirmation',
                    'label'   => t('Confirm It Is You'),
                    'columns' => 2,
                    'fields'  => [
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
                                            <i class="ph ph-fingerprint"></i> <?php echo t('Confirm with a Passkey'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
                [
                    'type'    => 'group',
                    'name'    => 'two-factor',
                    'label'   => t('Two-Factor Authentication'),
                    'columns' => 2,
                    'fields'  => [
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
                    'type'    => 'group',
                    'name'    => 'passkeys',
                    'label'   => t('Passkeys'),
                    'columns' => 2,
                    'fields'  => [
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
                                            <i class="ph ph-plus"></i> <?php echo t('Add Passkey'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php
                            },
                        ],
                    ],
                ],
                [
                    'type'    => 'group',
                    'name'    => 'sessions',
                    'label'   => t('Devices'),
                    'columns' => 2,
                    'fields'  => [
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
                                                <i class="ph ph-sign-out"></i> <?php echo t('Sign Out of All Other Devices'); ?>
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
                    'type'    => 'group',
                    'name'    => 'identities',
                    'label'   => t('Connected Accounts'),
                    'columns' => 2,
                    'fields'  => [
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
                                $error = $errors[$_GET['error'] ?? ''] ?? '';
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
                    'type'    => 'group',
                    'name'    => 'tokens',
                    'label'   => t('API Tokens'),
                    'columns' => 2,
                    'fields'  => [
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
                                                        <option value="30"><?php echo t('30 Days'); ?></option>
                                                        <option value="90" selected><?php echo t('90 Days'); ?></option>
                                                        <option value="365"><?php echo t('A Year'); ?></option>
                                                        <option value="0"><?php echo t('No Expiry'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="df fw fdc g-3 fs-13">
                                            <?php foreach ($permissions as $permission) : ?>
                                                <label class="df aic g-1"><input type="checkbox" name="token-scope" value="<?php echo htmlspecialchars($permission); ?>"> <?php echo htmlspecialchars($permission); ?></label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div>
                                            <button class="btn btn--outline" type="button" @click="$ajax.post('user/token-create', {name: document.getElementById('token-name').value, days: document.getElementById('token-days').value, scopes: [...document.querySelectorAll('[name=token-scope]:checked')].map(box => box.value).join(','), password: confirmPassword})">
                                                <i class="ph ph-plus"></i> <?php echo t('Create Token'); ?>
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
                    'type'    => 'group',
                    'name'    => 'activity',
                    'label'   => t('Recent Activity'),
                    'columns' => 2,
                    'fields'  => [
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
                    'type'    => 'group',
                    'name'    => 'passwords',
                    'label'   => t('Change Password'),
                    'columns' => 2,
                    'fields'  => [
                        [
                            'type'        => 'password',
                            'name'        => 'password-new',
                            'error'       => 'password',
                            'label'       => t('New Password'),
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
                                'u-prop'       => 'passwordNew',
                                'autocomplete' => 'new-password',
                                'placeholder'  => t('New Password'),
                            ],
                            'switcher'    => 1,
                            'generator'   => 1,
                            'indicator'   => 0,
                            'characters'  => [
                                'lowercase' => 2,
                                'uppercase' => 2,
                                'special'   => 2,
                                'length'    => Passwords::MIN_LENGTH,
                                'digit'     => 2,
                            ],
                        ],
                        [
                            'type'        => 'password',
                            'name'        => 'password-old',
                            'error'       => 'current',
                            'label'       => t('Old Password'),
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
                                'u-prop'         => 'passwordOld',
                                'autocomplete'   => 'current-password',
                                'u-autocomplete' => '',
                                'placeholder'    => t('Old Password'),
                            ],
                            'switcher'    => 1,
                            'generator'   => 0,
                            'indicator'   => 0,
	                        'characters'  => [
		                        'lowercase' => 2,
		                        'uppercase' => 2,
		                        'special'   => 2,
		                        'length'    => Passwords::MIN_LENGTH,
		                        'digit'     => 2,
	                        ],
                        ],
                        [
                            'type'        => 'submit',
                            'name'        => 'password-save',
                            'label'       => t('Update Password'),
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

if ($isNew) {
    Form::override($uid, fn (Form $form) => $form
        ->attributes(['@submit.prevent' => '$ajax.post("user/create", "", () => $dirty.remove($el))'])
        ->replace('password', [
            [
                'name'    => 'access',
                'type'    => 'tab',
                'label'   => t('Access'),
                'caption' => '',
                'icon'    => 'ph ph-key',
                'fields'  => [
                    [
                        'type'    => 'group',
                        'name'    => 'access',
                        'label'   => t('Access'),
                        'columns' => 2,
                        'fields'  => [
                            [
                                'type'        => 'select',
                                'name'        => 'role',
                                'error'       => 'role',
                                'label'       => t('Role'),
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
                                    'u-prop' => 'role',
                                    'value'  => Option::get('users.role'),
                                ],
                                'options'     => $roles,
                            ],
                            [
                                'type'        => 'password',
                                'name'        => 'password',
                                'error'       => 'password',
                                'label'       => t('Password'),
                                'class'       => '',
                                'label_class' => '',
                                'reset'       => 0,
                                'before'      => '',
                                'after'       => '',
                                'instruction' => t('Leave it empty to email the user a link to set the password.'),
                                'tooltip'     => '',
                                'copy'        => 0,
                                'validator'   => '',
                                'conditions'  => [],
                                'attributes'  => [
                                    'u-prop'       => 'password',
                                    'autocomplete' => 'new-password',
                                    'placeholder'  => t('Password'),
                                ],
                                'switcher'    => 1,
                                'generator'   => 1,
                                'indicator'   => 0,
                                'characters'  => [
                                    'lowercase' => 2,
                                    'uppercase' => 2,
                                    'special'   => 2,
                                    'length'    => Passwords::MIN_LENGTH,
                                    'digit'     => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]));
} elseif (! $isSelf) {
    Form::override($uid, fn (Form $form) => $form
        ->prepend([
            [
                'type'       => 'hidden',
                'name'       => 'id',
                'attributes' => ['type' => 'hidden', 'name' => 'id', 'value' => $user->id],
            ],
        ])
        ->replace('password', [
            [
                'name'    => 'administration',
                'type'    => 'tab',
                'label'   => t('Administration'),
                'caption' => '',
                'icon'    => 'ph ph-shield-check',
                'fields'  => [
                    [
                        'type'    => 'group',
                        'name'    => 'account',
                        'label'   => $user->showname ?: $user->login,
                        'columns' => 2,
                        'fields'  => [
                            [
                                'name'     => 'account',
                                'type'     => 'custom',
                                'callback' => function () use ($user) {
                                    echo view('parts/user-admin', Profile::admin($user));
                                },
                            ],
                        ],
                    ],
                ],
            ],
        ]));
}

return $uid;
