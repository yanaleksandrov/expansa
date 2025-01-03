<?php

declare(strict_types=1);

namespace Expansa\Post;

use Expansa\I18n;

class Status
{
    /**
     * Registers a post status. Do not use before init.
     *
     * A simple function for creating or modifying a post status based on the parameters given.
     * The function will accept an array (second optional parameter), along with a string for the post status name.
     *
     * Arguments prefixed with an _underscore shouldn't be used by plugins and themes.
     *
     * @since 2025.1
     *
     * @param string       $post_status Name of the post status.
     * @param array|string $args {
     *     Optional. Array or string of post status arguments.
     *
     *     @type bool|string $label                     A descriptive name for the post status marked
     *                                                  for translation. Defaults to value of $post_status.
     *     @type array|false $label_count               Nooped plural text from _n_noop() to provide the singular
     *                                                  and plural forms of the label for counts. Default false
     *                                                  which means the `$label` argument will be used for both
     *                                                  the singular and plural forms of this label.
     *     @type bool        $exclude_from_search       Whether to exclude posts with this post status
     *                                                  from search results. Default is value of $internal.
     *     @type bool        $_builtin                  Whether the status is built-in. Core-use only.
     *                                                  Default false.
     *     @type bool        $public                    Whether posts of this status should be shown
     *                                                  in the front end of the site. Default false.
     *     @type bool        $internal                  Whether the status is for internal use only.
     *                                                  Default false.
     *     @type bool        $protected                 Whether posts with this status should be protected.
     *                                                  Default false.
     *     @type bool        $private                   Whether posts with this status should be private.
     *                                                  Default false.
     *     @type bool        $publicly_queryable        Whether posts with this status should be publicly-
     *                                                  queryable. Default is value of $public.
     *     @type bool        $show_in_admin_all_list    Whether to include posts in the edit listing for
     *                                                  their post type. Default is the opposite value
     *                                                  of $internal.
     *     @type bool        $show_in_admin_status_list Show in the list of statuses with post counts at
     *                                                  the top of the edit listings,
     *                                                  e.g. All (12) | Published (9) | My Custom Status (2)
     *                                                  Default is the opposite value of $internal.
     *     @type bool        $date_floating             Whether the post has a floating creation date.
     *                                                  Default to false.
     * }
     * @return object
     */
    public static function register(...$data)
    {
    }

    public static function get(string $status = '', array $args = []): string
    {
        return '';
    }

    public static function getStatuses(): array
    {
        return [
            'publish'   => I18n::_t('Publish'),
            'pending'   => I18n::_t('Pending'),
            'draft'     => I18n::_t('Draft'),
            'protected' => I18n::_t('Protected'),
            'private'   => I18n::_t('Private'),
            'trash'     => I18n::_t('Trash'),
            'future'    => I18n::_t('Future'),
        ];
    }
}
