<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Http\VerifyCsrfToken;
use App\Models\Option;
use Expansa\Assets\Manager as AssetManager;
use Expansa\Facades\Asset;
use Expansa\Facades\Hook;
use Expansa\Facades\I18n;
use Expansa\Hooks\Attributes\Priority;
use Expansa\Support\Is;
use IntlDateFormatter;

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
     * Stylesheets of the vendor scripts of form fields, from dashboard/assets/css; the others style
     * themselves (youla-filler.js) or are styled by controls.css (youla-select.js).
     */
    private const array VENDOR_STYLES = ['youla-pickadate' => 'air-datepicker'];

    /**
     * Styles and scripts of a dashboard page, from dashboard/assets: loaded on that page only, after
     * the ones of the whole panel. Includes the vendor scripts of controls the page binds itself
     * instead of rendering the field templates, e.g. `u-select` on a plain `<select>`.
     *
     * @var array<string, array{styles?: string[], scripts?: string[]}>
     */
    private const array SCREENS = [
        'field-groups' => [
            'scripts' => ['youla-select'],
        ],
        'nav-editor'   => [
            'styles'  => ['air-datepicker', 'nav-editor'],
            'scripts' => ['youla-select', 'youla-pickadate', 'youla-nav-editor'],
        ],
    ];

    /**
     * Tokens of the PHP date format and their air-datepicker equivalents; the other letters have none
     * and are dropped.
     */
    private const array DATE_TOKENS = [
        'd' => 'dd',
        'j' => 'd',
        'D' => 'E',
        'l' => 'EEEE',
        'm' => 'MM',
        'n' => 'M',
        'M' => 'MMM',
        'F' => 'MMMM',
        'y' => 'yy',
        'Y' => 'yyyy',
        'H' => 'HH',
        'G' => 'H',
        'h' => 'hh',
        'g' => 'h',
        'i' => 'mm',
        'a' => 'aa',
        'A' => 'AA',
    ];

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

        $screen = self::SCREENS[self::screen()] ?? [];

        self::enqueue(
            ['phosphor', 'expansa', 'dialog', 'controls', 'utility', 'chat', ...$screen['styles'] ?? []],
            [
                'youla'         => ['data' => self::data()],
                'youla-ajax',
                'youla-expansa',
                'youla-tooltip',
                'youla-passkeys',
                'youla-chat',
                // global, not co-located: the media library must be reachable from any field on any page
                'youla-storage',
                ...$screen['scripts'] ?? [],
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

            Hook::add('renderDashboardAfter', static function () {
                echo view('parts/notifications');
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
     * Assets of a template for Assets\Manager::discover(): a form field gets the vendor JS of its type
     * and its stylesheet from VENDOR_STYLES, the rest the co-located files. date/range/color share
     * form/input.blade.php, so Renderer::render() passes the original subtype as `$context['type']`.
     *
     * @param string               $file
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    private static function structure(string $file, array $context = []): array
    {
        $file = str_replace('\\', '/', $file);

        if (! str_contains($file, '/dashboard/views/parts/form/')) {
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

        if ($vendor === null) {
            return AssetManager::defaultStructure($file);
        }

        $style = self::VENDOR_STYLES[$vendor] ?? null;

        return [
            'js' => EX_PATH . "dashboard/assets/js/$vendor" . self::suffix() . '.js',
            ...($style ? ['css' => EX_PATH . "dashboard/assets/css/$style" . self::suffix() . '.css'] : []),
        ];
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
            'dateFormat'          => self::dateFormat((string) Option::get('date-format', 'F j, Y')),
            'datepicker'          => self::datepicker(),
            'weekStart'           => (int) Option::get('week-starts-on', 1),
            'loadingText'         => t('Loading...'),
            'noResultsText'       => t('No results found'),
            'noChoicesText'       => t('No choices to choose from'),
            'uniqueItemText'      => t('Only unique values can be added.'),
            'customAddItemText'   => t('Only values matching specific conditions can be added.'),
            'showFilter'          => false,
            'bulk'                => false,
            'showMenu'            => false,
            'flagsUrl'            => url('/dashboard/assets/sprites/flags.svg'),
            'texts'               => [
                'deleteItem'    => t('Delete this item permanently?'),
                'deleteItems'   => t('Delete the selected items permanently?'),
                'emptyTrash'    => t('Delete all items in the trash permanently?'),
                'deleteComment' => t('Delete this comment? Its replies stay.'),
                'and'           => t('and'),
                'where'         => t('Where'),
                'is'            => t('is'),
                'isNot'         => t('is not'),
                'selected'      => t(':count selected'),
            ],
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
     * Texts of the datepicker: the names of days and months come from ICU in the dashboard locale,
     * months in the standalone form for titles and the month view, `monthsGenitive` in the form
     * used next to a day number ("19 октября").
     *
     * @return array<string, mixed>
     */
    private static function datepicker(): array
    {
        $names    = [];
        $patterns = [
            'days'           => 'cccc',
            'daysShort'      => 'ccc',
            'daysMin'        => 'cccccc',
            'months'         => 'LLLL',
            'monthsShort'    => 'LLL',
            'monthsGenitive' => 'MMMM',
        ];

        foreach ($patterns as $key => $pattern) {
            $formatter = new IntlDateFormatter(I18n::locale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', null, $pattern);
            $isMonth   = str_starts_with($key, 'months');

            // days from 4 January 1970, a Sunday, the first day of the locale arrays
            for ($i = 0; $i < ($isMonth ? 12 : 7); $i++) {
                $name          = $formatter->format($isMonth ? gmmktime(0, 0, 0, $i + 1, 1, 2000) : ($i + 3) * 86400);
                $names[$key][] = $key === 'months' || $key === 'monthsShort' ? mb_ucfirst($name) : $name;
            }
        }

        return [
            ...$names,
            'today'      => t('Today'),
            'clear'      => t('Clear'),
            'timeFormat' => self::dateFormat((string) Option::get('time-format', 'g:i a')),
        ];
    }

    /**
     * Convert a PHP date format of the settings to the air-datepicker one: "F j, Y" to "MMMM d, yyyy".
     * Its tokens match only between separators, so adjacent tokens get a space: "g:ia" to "h:mm aa".
     *
     * @param string $format PHP date format, a backslash escapes a letter.
     * @return string
     */
    private static function dateFormat(string $format): string
    {
        $result  = '';
        $isToken = false;
        $escaped = false;

        foreach (mb_str_split($format) as $char) {
            if ($escaped || $char === '\\') {
                $result .= $escaped ? $char : '';
                $escaped = ! $escaped;
                $isToken = false;
            } elseif (isset(self::DATE_TOKENS[$char])) {
                $result .= ($isToken ? ' ' : '') . self::DATE_TOKENS[$char];
                $isToken = true;
            } elseif (! ctype_alpha($char)) {
                $result .= $char;
                $isToken = false;
            }
        }

        return trim(preg_replace('/\s+/', ' ', $result));
    }

    /**
     * Get the slug of the requested dashboard page, the segment after the dashboard slug; empty for
     * the dashboard root.
     *
     * @return string
     */
    private static function screen(): string
    {
        $path     = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        $segments = explode('/', $path);
        $root     = array_search(trim((string) Hook::call('dashboardRootSlug', 'dashboard'), '/'), $segments, true);

        return $root === false ? '' : (string) ($segments[$root + 1] ?? '');
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
