<?php

declare(strict_types=1);

namespace App\Models;

use Expansa\Database\Model;
use Expansa\Database\Query;
use Expansa\Debug\Error;
use Expansa\Facades\Json;
use Expansa\Facades\Safe;
use Expansa\Support\Arr;
use LogicException;

/**
 * Represents a single row in the `options` table (one top-level settings key and its value),
 * the same way {@see User} represents one row in `users` — plus the public-facing key/value
 * facade on top of it (dot-notation access, an in-memory process cache, required defaults).
 *
 * A "dotted" key (e.g. "site.name") addresses a nested path inside one row's `value`, not a
 * row of its own — {@see self::get()}/{@see self::add()}/{@see self::update()}/{@see self::delete()}
 * are that facade and predate this class becoming a Model; they now do their persistence via
 * `fill()`/`save()`/`parent::get()` like every other model here, instead of raw `Db::` calls.
 *
 * Two of those facade methods — `get()` and `delete()` — happen to share a name with the
 * generic per-row lookup/delete {@see \Expansa\Database\Query} would otherwise expose through
 * {@see Model}'s magic methods. That generic behavior is reached from inside this class via
 * `parent::get(...)` (bypassing this class's own override, exactly like {@see User::find()}
 * does) and, for delete, by calling {@see Query} directly — see {@see self::delete()}. Because
 * of that same collision, this class has no separate User-style instance `update()`/`delete()`;
 * the facade methods below are the only ones, and they already do the fill/validate/save work.
 *
 * @property int    $id    Unique identifier of the option record.
 * @property string $key   The option's top-level key (unique).
 * @property mixed  $value The option's value. Stored JSON-encoded so any type round-trips,
 *                         including options written before this model existed (see value()).
 */
class Options extends Model
{
    use Model\HasSanitizing;
    use Model\HasValidation;

    /**
     * The database table associated with the model.
     *
     * @var string
     */
    protected string $table = 'options';

    /**
     * Fields allowed for mass assignment.
     *
     * @var array<string>
     */
    protected array $fillable = [
        'key',
        'value',
    ];

    /**
     * Options list, keyed by top-level option key, already JSON-decoded.
     *
     * @var array
     */
    private static array $options = [];

    /**
     * Suspend setting.
     *
     * @var bool
     */
    private static bool $suspend = false;

    /**
     * Array of rules for sanitize properties.
     *
     * @return array<string, string>
     */
    protected function getSanitizerRules(): array
    {
        return [
            'key' => 'id',
        ];
    }

    /**
     * An array of rules for validation when creating and updating a model.
     *
     * @return array<string, string>
     */
    protected function validatorRules(): array
    {
        return [
            'key' => 'required',
        ];
    }

    /**
     * Extend with custom validation rules.
     *
     * @return void
     */
    protected function validatorExtend(): void {}

    /**
     * JSON-encodes on write so any value type round-trips, and JSON-decodes on read.
     *
     * Falls back to the raw stored string when it isn't valid JSON — options written
     * before every value was JSON-encoded stored bare scalars (e.g. "UTF-8"), which
     * aren't valid JSON syntax and would otherwise silently decode to null.
     */
    protected function value(): Model\Attribute
    {
        return Model\Attribute::make(
            get: function ($value) {
                if (! is_string($value)) {
                    return $value;
                }

                $decoded = Json::decode($value, true);

                return $decoded === null && $value !== 'null' ? $value : $decoded;
            },
            set: fn($value) => Json::encode($value)
        );
    }

    /**
     * Get all options.
     */
    public static function fetch(): array
    {
        if (empty(self::$options)) {
            foreach (self::all() as $option) {
                self::$options[$option->key] = $option->value;
            }
        }
        return self::$options;
    }

    /**
     * Adds a setting (option name and value) to the database. Does nothing if the option already exists.
     * If an array or object is passed as a value, it is automatically converted to JSON format.
     * So you can store arrays in the settings. You can create options with an empty value and add a value later.
     * The value of the option can be passed as a string with dots as separators.
     * So the expression Option:add( 'site.name', 'some string' ) will create an option with the "site" key,
     * and json {"name": "some string"} is stored in the value field. Nesting depth is not limited.
     *
     * @param  string $option  Name of the option to retrieve.
     * @param  mixed  $value   Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @return int|bool        Number of rows added to the database or false, if option and value is exists.
     */
    public static function add(string $option, mixed $value): int|bool
    {
        $options = self::fetch();
        $option  = Safe::id($option);
        if (empty($option)) {
            return false;
        }

        /**
         * Add dotted options
         */
        if (str_contains($option, '.')) {
            return self::update($option, $value);
        }

        // skip adding if option is exist
        if (isset($options[ $option ])) {
            return false;
        }

        if (! self::$suspend) {
            self::$options[ $option ] = $value;
        }

        $record = new self()->fill(['key' => $option, 'value' => $value]);

        if (! $record->isValid()) {
            return false;
        }

        return $record->save() instanceof self ? 1 : false;
    }

    /**
     * Updates the value of an option that was already added.
     *
     * @param string $option  Name of the option to retrieve.
     * @param mixed  $value   Option value as a number, string, or array. The array will be serialized.
     * @return bool|int       Number of updated rows or false, if option and value is exists.
     */
    public static function update(string $option, mixed $value): bool|int
    {
        $option = Safe::id($option);
        if (empty($option)) {
            return false;
        }

        /**
         * Update dotted options
         */
        $options = self::fetch();
        if (str_contains($option, '.')) {
            $new = [];
            $old = Arr::get($options, $option);
            Arr::set($new, $option, $value);

            if ($value === $old) {
                return false;
            }

            [ $option ] = explode('.', $option, 2);
            $value      = array_replace_recursive($options[ $option ] ?? [], $new[ $option ]);
        }

        if (! isset($options[ $option ])) {
            return self::add($option, $value);
        }

        if ($options[ $option ] === $value) {
            return false;
        }

        if (! self::$suspend) {
            self::$options[ $option ] = $value;
        }

        // Bypasses this class's own get() facade — see the class docblock.
        $record = parent::get($option, 'key');
        if (! $record instanceof self) {
            return false;
        }

        $record->fill(['value' => $value]);

        return $record->save() instanceof self ? 1 : false;
    }

    /**
     * Retrieves an option value based on an option name.
     *
     * If the option does not exist or does not have a value, then the return value
     * will be default value. This is useful to check whether you need to install an option
     * and is commonly used during installation of plugin options and to test
     * whether upgrading is required.
     *
     * Speed benchmark: 1 time = 0.000005 sec. | 100000 time = 0.03 sec
     *
     * @param  string $option  Name of the option to retrieve.
     * @param  mixed  $default Optional. Default value to return if the option does not exist.
     * @return mixed           Value set for the option. A value of any type may be returned, including
     *                         array, boolean, float, integer, null, object, and string.
     */
    public static function get(string $option, mixed $default = ''): mixed
    {
        $options = self::fetch();

        /**
         * Get dotted options
         */
        if (str_contains($option, '.')) {
            return Arr::get($options, $option) ?? $default;
        }

        return $options[ $option ] ?? $default;
    }

    /**
     * Removes option by name. Prevents removal of protected Expansa options.
     *
     * @param string $option  Name of the option to retrieve.
     * @return int|Error     Count of deleted rows.
     */
    public static function delete(string $option): int|Error
    {
        /**
         * Delete dotted options
         */
        if (str_contains($option, '.')) {
            return self::update($option, '');
        }

        $not_allowed_options = array_keys(self::default());
        if (in_array($option, $not_allowed_options, true)) {
            throw new LogicException(
                t('You are not allowed to delete the ":optionName" option. You can just update it.', $option)
            );
        }

        if (!self::$suspend) {
            unset(self::$options[ $option ]);
        }

        // Bypasses this class's own get() facade — see the class docblock.
        $record = parent::get($option, 'key');
        if (! $record instanceof self) {
            return 0;
        }

        // Goes through Query directly, not $record->delete() — this class's own delete()
        // facade (this very method) would otherwise shadow it. See the class docblock.
        return new Query($record)->delete();
    }

    /**
     * Update the option if the value is not empty and defined, otherwise delete.
     * Return "true" if the value has updated or deleted, and "false" if nothing has changed in the database.
     *
     * @param  string $option  Name of the option to retrieve.
     * @param  mixed  $value   Option value as a number, string, or array. The array will be converted to JSON.
     * @return bool
     */
    public static function modify(string $option, mixed $value): bool
    {
        if (! empty($value)) {
            $updated = self::update($option, $value);
        } else {
            $deleted = self::delete($option);
        }
        return ( isset($deleted) && $deleted ) || ( isset($updated) && $updated );
    }

    /**
     * Prints option value after sanitizing for html attribute.
     *
     * @param  string $option  Name of the option to retrieve.
     * @param  mixed  $default Optional. Default value to return if the option does not exist.
     * @return string
     */
    public static function attr(string $option, mixed $default = ''): string
    {
        return Safe::attribute(self::get($option, $default));
    }

    /**
     * Temporarily suspend cache additions.
     *
     * Stops more data being added to the cache, but still allows cache retrieval.
     * This is useful for actions, such as imports, when a lot of data would otherwise
     * be almost uselessly added to the cache.
     *
     * Suspension lasts for a single page load at most.
     * Remember to call this function again if you wish to re-enable cache adds earlier.
     *
     * @param  bool $suspend  Optional. Suspends additions if true, re-enables them if false.
     * @return bool           The current suspend setting.
     */
    public function suspendAddition(bool $suspend): bool
    {
        if (false === $suspend) {
            self::$options = [];
            foreach (self::all() as $option) {
                self::$options[$option->key] = $option->value;
            }
        }
        return self::$suspend = $suspend;
    }

    /**
     * Returns a list of default options.
     *
     * It is used to load them into the database during the initial Expansa installation.
     * You can't delete them, but you can only change them.
     *
     * @return array Options list.
     */
    public static function default(): array
    {
        return [
            'charset'        => 'UTF-8',
            'site'           => [
                'url'      => '',
                'name'     => '',
                'tagline'  => '',
                'language' => '',
            ],
            'owner'          => [
                'email' => '',
            ],
            'users'          => [
                'default-role' => 'subscriber',
                'membership'   => 0,
            ],
            'week-starts-on' => 1,
            'date-format'    => 'F j, Y',
            'time-format'    => 'g:i a',
            'timezone'       => [
                'name'   => date_default_timezone_set('Europe/London'),
                'offset' => 0,
            ],
            'comments'       => [
                'status'                => 'open',
                'requires_registration' => 0,
                'close_after_days'      => 14,
                'depth'                 => 5,
                'per_page'              => 50,
                'order'                 => 'ASC',
                'max_links'             => 0,
                'cookies_enabled'       => 1,
                'previously_approved'   => 1,
                'moderation'            => 0,
            ],
        ];
    }
}
