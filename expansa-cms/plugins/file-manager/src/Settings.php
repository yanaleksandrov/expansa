<?php

declare(strict_types=1);

namespace Plugins\FileManager;

use App\Models\Option;
use Expansa\Http\Exceptions\HttpError;

/**
 * Settings of the file manager from the `file_manager` option, changed in its settings dialog:
 * the root folder, hidden paths, extensions that cannot be created, read-only mode and the trash.
 *
 * @package Plugins\FileManager
 */
final readonly class Settings
{
    /**
     * Option that keeps the settings.
     */
    public const string OPTION = 'file_manager';

    /**
     * Values before the first save: secrets and code stay out of reach.
     */
    private const array DEFAULTS = [
        'root'     => '',
        'hidden'   => ['env.php', '.env', '.git'],
        'blocked'  => ['php', 'phtml', 'phar', 'htaccess'],
        'readonly' => false,
        'trash'    => true,
    ];

    public function __construct(

        /**
         * Absolute root folder, empty for the installation folder.
         */
        public string $root,

        /**
         * Patterns of fnmatch() hiding paths relative to the root or names at any depth.
         *
         * @var string[]
         */
        public array $hidden,

        /**
         * Lowercase extensions that cannot be uploaded, created, renamed to or extracted.
         *
         * @var string[]
         */
        public array $blocked,

        /**
         * Whether changes are forbidden.
         */
        public bool $readonly,

        /**
         * Whether deleted entries go to the trash.
         */
        public bool $trash,
    ) {}

    /**
     * Saved settings over the defaults.
     *
     * @return self
     */
    public static function load(): self
    {
        $saved = (array) Option::get(self::OPTION, []);

        return self::normalize([...self::DEFAULTS, ...$saved]);
    }

    /**
     * Check and save the values of the settings form.
     *
     * @param array<string, mixed> $input `root`, `hidden` and `blocked` as text, `readonly` and `trash` as flags.
     * @return self
     */
    public static function save(array $input): self
    {
        $settings = self::normalize($input);
        if ($settings->root !== '' && ! is_dir($settings->root)) {
            throw new HttpError(422, t('The folder :path does not exist.', $settings->root));
        }

        Option::update(self::OPTION, [
            'root'     => $settings->root,
            'hidden'   => $settings->hidden,
            'blocked'  => $settings->blocked,
            'readonly' => $settings->readonly,
            'trash'    => $settings->trash,
        ]);

        return $settings;
    }

    /**
     * Explorer of the root folder with these settings and the trash in the storage.
     *
     * @return Explorer
     */
    public function createExplorer(): Explorer
    {
        return new Explorer(
            root: $this->root === '' ? EX_PATH : $this->root,
            hidden: $this->hidden,
            blocked: $this->blocked,
            trash: $this->trash ? new Trash(str_replace('\\', '/', EX_STORAGE) . 'file-manager/trash') : null,
        );
    }

    /**
     * Settings from raw values: lists from text lines or commas, flags from form strings.
     *
     * @param array<string, mixed> $values
     * @return self
     */
    private static function normalize(array $values): self
    {
        $list = static fn (mixed $value, string $separator): array => array_values(array_unique(array_filter(
            array_map(trim(...), is_array($value) ? $value : explode($separator, str_replace("\r", '', (string) $value))),
            static fn (string $item) => $item !== ''
        )));

        return new self(
            root: rtrim(trim((string) ($values['root'] ?? '')), '/\\'),
            hidden: $list($values['hidden'] ?? [], "\n"),
            blocked: array_map(
                static fn (string $extension) => strtolower(ltrim($extension, '.')),
                $list($values['blocked'] ?? [], ',')
            ),
            readonly: filter_var($values['readonly'] ?? false, FILTER_VALIDATE_BOOLEAN),
            trash: filter_var($values['trash'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
