<?php

declare(strict_types=1);

namespace Expansa;

use Expansa\Support\Arr;

/**
 * Option class it is a self-contained class for creating, updating, and deleting options.
 * Uses static variables to store options, which allows to avoid using the object cache without losing performance.
 */
final class Option extends Option\Schema
{
    /**
     * Options list
     *
     * @var   array
     */
    private static array $options = [];

    /**
     * Required options that cannot be deleted.
     *
     * @var   array
     */
    private static array $required = [];

    /**
     * Suspend setting.
     *
     * @var   bool
     */
    private static bool $suspend = false;

    /**
     * Get all options.
     */
    public static function fetch(): array
    {
        if (empty(self::$options)) {
            $options = Db::select(self::$table, '*');
            if ($options) {
                $options = array_column($options, 'value', 'key');
                foreach ($options as $key => $value) {
                    self::$options[ $key ] = Json::decode($value, true);
                }
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
         *
         * @since 1.0.1
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

        /**
         * Encode to json for array or object.
         *
         * @since 1.0.1
         */
        if (is_array($value) || is_object($value)) {
            $value = Json::encode($value);
        }

        return Db::insert(
            self::$table,
            [
                'key'   => $option,
                'value' => $value,
            ]
        )->rowCount();
    }

    /**
     * Updates the value of an option that was already added.
     *
     * @param string $option  Name of the option to retrieve.
     * @param mixed  $value   Option value as a number, string, or array. The array will be serialized.
     * @return int            Number of updated rows or false, if option and value is exists.
     */
    public static function update(string $option, mixed $value): bool|int
    {
        $option = Safe::id($option);
        if (empty($option)) {
            return false;
        }

        /**
         * Update dotted options
         *
         * @since 1.0.1
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

        /**
         * Encode to json for array or object.
         *
         * @since 1.0.1
         */
        if (is_array($value) || is_object($value)) {
            $value = Json::encode($value);
        }

        return Db::update(self::$table, [ 'value' => $value ], [ 'key[=]' => $option ])->rowCount();
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
         *
         * @since 1.0.1
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
         *
         * @since 1.0.1
         */
        if (str_contains($option, '.')) {
            return self::update($option, '');
        }

        $not_allowed_options = array_keys(self::default());
        if (in_array($option, $not_allowed_options, true)) {
            return new Error(
                'option-delete',
                sprintf(
                    I18n::_t('You are not allowed to delete the "%s" option. You can just update it.'),
                    $option
                )
            );
        }

        if (isset(self::$options[ $option ]) && ! self::$suspend) {
            unset(self::$options[ $option ]);
        }

        return Db::delete(
            self::$table,
            [
                'key' => $option,
            ]
        )->rowCount();
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
    public function suspend_addition(bool $suspend): bool
    {
        if (false === $suspend) {
            $options = Db::select(self::$table, '*');
            if ($options) {
                self::$options = array_column($options, 'value', 'key');
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
