<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Http\VerifyCsrfToken;
use Expansa\Assets\Manager as AssetManager;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Support\Is;

/**
 * Styles and scripts of the dashboard pages, from dashboard/assets, minified outside debug mode.
 * Each context loads its own set: the dashboard the whole panel, the sign-in pages and the installer
 * only what their forms need.
 *
 * @package App\Dashboard
 */
final class Assets
{
    /**
     * Whether the notifications markup is already hooked into the page footer.
     */
    private static bool $notifications = false;

    /**
     * Assets of the dashboard: the whole panel, its data for the scripts and the vendor JS of form fields.
     *
     * @return void
     */
    public static function dashboard(): void
    {
        self::favicons();

        // vendor JS of a form field loads only on the pages that render that field
        AssetManager::configure(resolver: self::structure(...));

        self::enqueue(
            ['phosphor', 'expansa', 'dialog', 'controls', 'utility', 'nav-editor', 'chat'],
            [
                'youla'         => ['data' => self::data()],
                'youla-ajax',
                'youla-expansa',
                'youla-passkeys',
                'youla-chat',
                // global, not co-located: the media library must be reachable from any field on any page
                'youla-storage',
            ]
        );
    }

    /**
     * Assets of the sign-in, sign-up and reset-password pages: the styles of their forms and the
     * runtime that submits them, without the panel.
     *
     * @return void
     */
    public static function auth(): void
    {
        self::favicons();

        self::enqueue(
            ['phosphor', 'expansa', 'controls', 'utility'],
            [
                // a key, not a name: enqueue() adds the `$ajax` settings to its data
                'youla' => [],
                'youla-ajax',
                'youla-expansa',
                'youla-passkeys',
            ]
        );
    }

    /**
     * Assets of the installer.
     *
     * @return void
     */
    public static function install(): void
    {
        self::enqueue(
            ['expansa', 'controls', 'utility', 'phosphor'],
            [
                'youla' => [
                    'data' => [
                        'flagsUrl' => url('/dashboard/assets/sprites/flags.svg'),
                    ],
                ],
                'youla-ajax',
                'youla-select',
                'youla-expansa',
            ]
        );
    }

    /**
     * Enqueue styles and scripts in the given order. Also seeds the CSRF cookie that youla-ajax.js sends
     * back with every request and adds the notifications of `$notice` and the `notify` API fragments.
     * The `youla` data gets `ajax`, the settings of youla-ajax.js; youla-expansa.js puts them into `Youla.ajax`.
     *
     * @param string[]                 $styles  Style names, e.g. ['expansa', 'controls'].
     * @param array<int|string, mixed> $scripts Script names, or name => extra Asset::script() data.
     * @return void
     */
    private static function enqueue(array $styles, array $scripts): void
    {
        VerifyCsrfToken::seed();

        $suffix = self::suffix();

        if (isset($scripts['youla'])) {
            $scripts['youla']['data']['ajax'] ??= self::ajax();
        }

        foreach (array_unique([...$styles, 'notifications']) as $style) {
            Asset::style($style, url("/dashboard/assets/css/$style$suffix.css"));
        }

        foreach ($scripts as $key => $value) {
            [$script, $data] = is_int($key) ? [$value, []] : [$key, $value];

            Asset::script($script, url("/dashboard/assets/js/$script$suffix.js"), $data);
        }

        if (! self::$notifications) {
            self::$notifications = true;

            Hook::add('renderDashboardFooter', static function () {
                echo view('components/notifications');
            });
        }
    }

    /**
     * Favicons and the web manifest of the dashboard.
     *
     * @return void
     */
    private static function favicons(): void
    {
        $icons = [
            'favicon'       => ['href' => 'favicon-96x96.png', 'rel' => 'icon', 'type' => 'image/png', 'sizes' => '96x96'],
            'favicon-svg'   => ['href' => 'favicon.svg', 'rel' => 'icon', 'type' => 'image/svg+xml'],
            'favicon-ico'   => ['href' => 'favicon.ico', 'rel' => 'shortcut icon', 'type' => ''],
            'favicon-apple' => ['href' => 'apple-touch-icon.png', 'rel' => 'apple-touch-icon', 'sizes' => '180x180', 'type' => ''],
            'manifest'      => ['href' => 'site.webmanifest', 'rel' => 'manifest', 'type' => ''],
        ];

        foreach ($icons as $uid => $icon) {
            $icon['href'] = url('/dashboard/assets/favicon/' . $icon['href']);

            Asset::style($uid, $icon['href'], $icon);
        }
    }

    /**
     * Assets of a template for Assets\Manager::discover(): a form field gets the vendor JS of its type,
     * the rest the co-located files. date/range/color share form/input.blade.php, so Renderer::render()
     * passes the original subtype as `$context['type']` to tell them apart.
     *
     * @param string               $file
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    private static function structure(string $file, array $context = []): array
    {
        if (! str_contains(str_replace('\\', '/', $file), '/dashboard/views/components/form/')) {
            return AssetManager::defaultStructure($file);
        }

        $template      = basename($file, '.blade.php');
        $inputType     = $context['type'] ?? '';
        $dateTimeTypes = ['date', 'datetime-local', 'time', 'month', 'week'];

        $vendor = match (true) {
            $template === 'select'                           => 'youla-select',
            $inputType === 'color'                           => 'youla-filler',
            in_array($inputType, $dateTimeTypes, true) => 'youla-pickadate',
            $inputType === 'range'                           => 'youla-ranger',
            default                                          => null,
        };

        return $vendor === null
            ? AssetManager::defaultStructure($file)
            : ['js' => EX_PATH . "dashboard/assets/js/$vendor" . self::suffix() . '.js'];
    }

    /**
     * Data of the dashboard scripts as the `youla` variable: locale, datepicker texts, dialogs;
     * a plugin changes it with the `dashboardData` filter.
     *
     * @return array<string, mixed>
     */
    private static function data(): array
    {
        return Hook::call('dashboardData', [
            'items'               => [],
            'locale'              => I18n::locale(),
            'dateFormat'          => 'd MMMM, yyyy',
            'datepicker'          => [
                'days'        => [
                    t('Sunday'),
                    t('Monday'),
                    t('Tuesday'),
                    t('Wednesday'),
                    t('Thursday'),
                    t('Friday'),
                    t('Saturday')
                ],
                'daysShort'   => [
                    t('Sun'),
                    t('Mon'),
                    t('Tue'),
                    t('Wed'),
                    t('Thu'),
                    t('Fri'),
                    t('Sat')
                ],
                'daysMin'     => [
                    t('Su'),
                    t('Mo'),
                    t('Tu'),
                    t('We'),
                    t('Th'),
                    t('Fr'),
                    t('Sa')
                ],
                'months'      => [
                    t('January'),
                    t('February'),
                    t('March'),
                    t('April'),
                    t('May'),
                    t('June'),
                    t('July'),
                    t('August'),
                    t('September'),
                    t('October'),
                    t('November'),
                    t('December')
                ],
                'monthsShort' => [
                    t('Jan'),
                    t('Feb'),
                    t('Mar'),
                    t('Apr'),
                    t('May'),
                    t('Jun'),
                    t('Jul'),
                    t('Aug'),
                    t('Sep'),
                    t('Oct'),
                    t('Nov'),
                    t('Dec')
                ],
                'today'       => t('Today'),
                'clear'       => t('Clear'),
                'dateFormat'  => 'MM/dd/yyyy',
                'timeFormat'  => 'hh:mm aa',
                'firstDay'    => 0,
            ],
            'weekStart'           => 1,
            'loadingText'         => t('Loading...'),
            'noResultsText'       => t('No results found'),
            'noChoicesText'       => t('No choices to choose from'),
            'uniqueItemText'      => t('Only unique values can be added.'),
            'customAddItemText'   => t('Only values matching specific conditions can be added.'),
            'showFilter'          => false,
            'bulk'                => false,
            'showMenu'            => false,
            'flagsUrl'            => url('/dashboard/assets/sprites/flags.svg'),
            'notifications'       => [
                'ctrlS' => t_attr('Expansa saves your changes automatically, so there\'s no need to press ⌘ + S.'),
            ],
            'uploaderDialog'      => [
                'title' => t('Upload Files'),
                'class' => 'dialog--md',
            ],
            'emailDialog'         => [
                'title' => t('Email Settings'),
                'class' => 'dialog--xl dialog--right',
            ],
            'postEditorDialog'    => [
                'title' => t('Post Editor'),
                'class' => 'dialog--lg dialog--right',
            ],
            'takeSelfieDialog'    => [
                'title' => t('Take a Selfie'),
                'class' => 'dialog--sm',
            ],
            'mediaLibraryDialog'  => [
                'title' => t('Media Library'),
                'class' => 'dialog--xl',
            ],
        ]);
    }

    /**
     * Settings of youla-ajax.js: the API address, the CSRF cookie and header, where the errors of
     * a field go in the form builder markup and the texts of a request that failed without a message.
     *
     * @return array<string, mixed>
     */
    private static function ajax(): array
    {
        return [
            'baseURL'  => url('/api/'),
            'csrf'     => ['cookie' => VerifyCsrfToken::COOKIE, 'header' => VerifyCsrfToken::HEADER],
            'errors'   => [
                'field'        => '[data-error="{name}"]',
                'wrapper'      => '.field',
                'anchor'       => '.field-item',
                'messageClass' => 'field-error',
                'invalidClass' => 'is-invalid',
            ],
            'messages' => [
                'failed'  => t('Something went wrong. Please try again later.'),
                'network' => t('No connection. Check the internet and try again.'),
            ],
        ];
    }

    /**
     * Suffix of the asset files: minified outside debug mode.
     *
     * @return string
     */
    private static function suffix(): string
    {
        return Is::debug() ? '' : '.min';
    }
}
