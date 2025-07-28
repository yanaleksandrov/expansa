<?php

declare(strict_types=1);

namespace App\Query;

use App\Post;
use App\Post\Type;
use App\User;
use Expansa\Facades\Db;
use Expansa\Facades\Safe;

class Query
{
    /**
     * SQL for the database query.
     *
     * @var   string
     */
    public string $request;

    /**
     * Query vars set by the user.
     *
     * @var array
     */
    public array $query = [];

    /**
     * Query vars set by the user.
     *
     * @var int
     */
    public static int $foundRows = 0;

    /**
     * Parse a query string and set query type booleans.
     *
     * @param array $query {
     *     Optional. Array or string of Query parameters.
     *
     *     @type string|string[]  $type            A post type slug (string) or array of post type slugs.
     *                                                 * `[ 'pages', 'your_custom_type' ]` - array of names (label) of custom post type.
     *                                                 * `pages` - by default.
     *
     *     @type string           $author_nicename User 'nicename'.
     *     @type int[]            $author__in      An array of author IDs to query from.
     *     @type int[]            $author__not_in  An array of author IDs not to query from.
     *
     *     @type string           $title           Post type title.
     *     @type string[]         $status          An array of post statuses. By default, publish, and if the user is authorized, private is also added.
     *                                                 * `[ 'publish' ]` - (default) published post.
     *                                                 * `[ 'pending' ]` - the post is under moderation.
     *                                                 * `[ 'draft' ]` - draft.
     *                                                 * `[ 'protected' ]` - password protected post.
     *                                                 * `[ 'private' ]` - personal post.
     *                                                 * `[ 'trash' ]` - deleted post (in the trash).
     *                                                 * `[ 'future' ]` - scheduled post. When the date is reached, JitBit will change it to the `publish` status.
     *     @type string           $slug            Post type slug.
     *     @type boolean          $slug_strict     The default is `true`, which means that posts are selected by an exact match of the column value.
     *                                             If `false`, the selection is made by the regular expression '^YOUR-SLUG(-[:digit:]]+)?$'.
     *                                             This is useful for creating records with a unique slug.
     *
     *     @type int[]            $post__in        An array of post IDs to retrieve, sticky posts will be included.
     *     @type int[]            $post__not_in    An array of post IDs not to retrieve. Note: a string of comma-separated IDs will NOT work.
     *     @type int[]            $parent__in      An array containing parent page IDs to query child pages from. Use `[ 0 ]` to only retrieve top-level pages.
     *     @type int[]            $parent__not_in  An array containing parent page IDs not to query child pages from.
     *
     *     @type array|int        $comments        Filter results by comment count. Provide an integer to match
     *                                                comment count exactly. Provide an array with integer 'value'
     *                                                and 'compare' operator `('=', '!=', '>', '>=', '<', '<=' )` to
     *                                                compare against `comments` in a specific way.
     *     @type string           $discussion      Discussion status.
     *
     *     @type string           $s               Search keyword(s).
     *     @type boolean          $sentence        If `false` is set (default) the search phrase is divided into words
     *                                             and searched by words,otherwise it searches by the full search phrase.
     *                                             Work only with boolean_mode=true.
     *     @type boolean          $boolean_mode    If `false` (by default) it means that Query perform a natural language search for a string.
     *                                             If `true` your can use the logical operators AND, OR and NOT to determine whether the strings match the search query.
     *                                             The following operators are supported in the logical full-text search mode:
     *                                                 * `+` - A plus sign preceding a word indicates that the word must be present in each line returned.
     *                                                 * `-` - The minus sign preceding a word means that the word must not be present in any of the returned strings.
     *                                                 * `()` - Round brackets group words into sub-expressions
     *                                                 * `~` - The squiggle preceding a word acts as a negation operator, causing a negative contribution of the word to the relevance of the string. It marks undesirable words. The line containing such word will be evaluated lower than others, but will not be excluded completely, as in the case of the operator - "minus".
     *                                                 * `*` - The asterisk is a truncation operator.
     *                                                 * `"` - A phrase enclosed in double quotation marks corresponds only to lines containing that phrase, written literally.
     *
     *     @type string           $order           Designates ascending or descending order of posts. Default 'DESC'.
     *                                                 Accepts `ASC`, `DESC`.
     *     @type string|array     $orderby         Sort retrieved posts by parameter. One or more options may be passed.
     *                                             TODO: add order by meta values
     *                                             To use 'meta_value', or 'meta_value_num', 'meta_key=keyname' must be also be defined.
     *                                             To sort by a specific `$meta_query` clause, use that clause's array key. Accepts:
     *                                                 - 'ID'
     *                                                 - 'slug'
     *                                                 - 'author'
     *                                                 - 'created' (default)
     *                                                 - 'title'
     *                                                 - 'modified'
     *                                                 - 'parent'
     *                                                 - 'rand'
     *                                                 - 'comments'
     *                                                 - 'post__in'
     *                                                 - 'post_name__in'
     *                                                 - 'post_parent__in'
     *                                             In `orderby`, you can specify an array combining both parameters: `orderby` and `order`.
     *                                             This is done to sort by several columns at the same time.
     *                                             The syntax is as follows: `'orderby' => [ 'title' => 'ASC', 'views' => 'DESC' ]`
     *     @type int              $page            Pagination page number. Show posts that normally should have been shown on the pagination X page.
     *     @type int|int[]        $perPage        The number of posts per page.
     *     @type int              $offset          How many posts to skip from the top of the selection (top indent).
     *                                             You can set the indentation from the first post in the query results.
     *                                             For example, a standard query returns 10 posts, if you add the parameter `offset=1`
     *                                             to the same query, it will also return 10 posts, but will skip the first post.
     *                                             This parameter does not interrupt the `page` parameter and does not break the pagination.
     * }
     */
    public static function apply($args = [], $callback = null)
    {
        $where = [];
        $args  = array_merge(
            [
                // search
                's'               => '',
                'sentence'        => false,
                'boolean_mode'    => false,
                // posts data
                'type'            => 'pages',
                'author_nicename' => '',
                'author__in'      => '',
                'author__not_in'  => '',
                'slug'            => '',
                'slug_strict'     => true,
                'title'           => '',
                'status'          => 'publish',
                'post__in'        => [],
                'post__not_in'    => [],
                'parent__in'      => [],
                'parent__not_in'  => [],
                // comments & views count
                'comments'        => '',
                'views'           => '',
                // order
                'order'           => 'DESC',
                'orderby'         => 'created_at',
                // pagination, offset, dates & fields
                'page'            => 1,
                'per_page'        => 10,
                'offset'          => 0,
                'dates'           => [],
                'fields'          => [],
            ],
            $args
        );

        /**
         * Parse search parameter.
         */
        $search = ( new Search($args) )->parse($args['s'] ?? '');

        /**
         * Parse custom fields.
         */
        $custom_fields = self::parseFields($args);
        if ($custom_fields) {
            $where[] = $custom_fields;
        }

        /**
         * Builds the main SQL query for the specified post types.
         */
        $queries = [];

        $postTypes = Safe::array($args['type'] ?? []);
        foreach ($postTypes as $postType) {
            $postTypeKey   = Safe::trim($postType);
            $postTypeTable = Safe::snakecase($postType);

            if (! Type::exist($postTypeKey)) {
                continue;
            }

            $table = EX_DB_PREFIX . $postTypeTable;
            $join = $custom_fields ? " INNER JOIN `{$table}_fields` ON ($table.id = {$table}_fields.post_id)" : '';

            $queries[] = trim("SELECT *, '$postTypeTable' AS type FROM `$table` $join $search");
        }

        if (!$queries) {
            return [];
        }

        $query = sprintf('SELECT SQL_CALC_FOUND_ROWS * FROM (%s) AS t', implode(' UNION ALL ', $queries));

        /**
         * Code for creating the WHERE part of an SQL query.
         *
         * Authors/users stuff, posts IDs & parents posts IDs
         */
        $nicename = Safe::text($args['nicename'] ?? '');
        $user     = $nicename ? User::get($nicename, 'nicename') : null;
        if ($user instanceof User) {
            $author__in         = (array) ( $args['author__in'] ?? [] );
            $args['author__in'] = $author__in + [ $user->id ];
        }

        $fields = [
            'author__in'     => 'author',
            'author__not_in' => 'author',
            'post__in'       => 'ID',
            'post__not_in'   => 'ID',
            'parent__in'     => 'parent',
            'parent__not_in' => 'parent',
        ];

        foreach ($fields as $field => $column) {
            $values = Safe::array($args[ $field ] ?? []);

            $values   = implode(',', array_map('intval', array_unique($values)));
            $operator = str_contains($field, 'not') ? 'NOT IN' : 'IN';
            if ($column && $values) {
                $where[] = sprintf('t.%s %s (%s)', $column, $operator, $values);
            }
        }

        /**
         * Post title
         */
        $title = Safe::text($args['title'] ?? '');
        if ($title) {
            $where[] = "t.title = '$title'";
        }

        /**
         * Post statuses.
         */
        $allowedStatuses = Type::fetch();
        $statuses        = Safe::array($args['status'] ?? []);
        $theStatuses     = [];
        foreach ($statuses as $status) {
            if (in_array($status, $allowedStatuses, true)) {
                $theStatuses[] = sprintf("'%s'", $status);
            }
        }

        if ($theStatuses) {
            $where[] = 't.status IN (' . implode(',', $theStatuses) . ')';
        }

        /**
         * Post slug
         */
        $slug        = Safe::slug($args['slug'] ?? '');
        $slug_strict = Safe::bool($args['slug_strict'] ?? true);
        if (! empty($slug)) {
            $where[] = $slug_strict ? "t.slug = '$slug'" : "t.slug REGEXP '^$slug(-[[:digit:]]+)?$'";
        }

        /**
         * Matching by comments count & views.
         */
        $fields = [ 'comments', 'views' ];
        foreach ($fields as $field) {
            $comments = $args[ $field ] ?? '';
            if (empty($comments)) {
                continue;
            }

            if (is_numeric($comments)) {
                $comments = [
                    'value' => (int) $comments,
                ];
            }
            if (isset($comments['value'])) {
                $comments = array_merge(
                    [
                        'compare' => '=',
                    ],
                    $comments
                );
                // Fallback for invalid compare operators is '='.
                $compare_operators = [ '=', '!=', '>', '>=', '<', '<=' ];
                if (! in_array($comments['compare'], $compare_operators, true)) {
                    $comments['compare'] = '=';
                }
                $where[] = sprintf("t.%s {$comments['compare']} %d", $field, $comments['value']);
            }
        }

        /**
         * Matching by posts discussion status.
         */
        $discussion = Safe::trim($args['discussion'] ?? '');
        if ($discussion) {
            $where[] = "t.discussion = '$discussion'";
        }

        /**
         * Parse dates.
         */
        $dates = self::parseDates($args['dates'] ?? []);
        if ($dates) {
            $where[] = $dates;
        }

        /**
         * Implode where part
         */
        if ($where) {
            $query .= 'WHERE
			' . implode(' AND ', $where);
        }

        $query .= ' GROUP BY t.id, type';

        /**
         * Order.
         */
        $orderby = self::parseOrderBy($args['orderby'] ?? '', $args, self::parseOrder($args['order'] ?? ''));
        if ($orderby) {
            $query .= sprintf(' ORDER BY %s', $orderby);
        }

        /**
         * Limits for pagination.
         */
        $limit = self::parseLimit($args);
        if ($limit) {
            $query .= sprintf(' LIMIT %s', $limit);
        }

        /**
         * Run query.
         */
        $data = Db::query($query);
        if ($data) {
            $items = $data->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($items as $i => $item) {
                $item = array_combine(array_map([Safe::class, 'camelcase'], array_keys($item)), $item);

                $items[ $i ] = new Post(...$item);
            }

            $foundRows = Db::query('SELECT FOUND_ROWS()')->fetchAll(\PDO::FETCH_NUM);
            if ($foundRows) {
                self::$foundRows = intval($found_posts[0][0] ?? 0);
            }

            if (is_callable($callback)) {
                $items = call_user_func($callback, $query, $items);
            }

            return $items;
        }

        return [];
    }

    /**
     * Method for generating SQL clauses that filter a primary query according to date.
     * Is a helper that allows primary query class, to filter their results by date columns,
     * by generating `WHERE` subclauses to be attached to the primary SQL query string.
     *
     * When trying to filter by an invalid date value (for example, month=13),
     * it will try to bring the value to the maximum allowable.
     *
     * Time-related parameters that normally require integer values ('year', 'month', 'week', 'dayofyear', 'dayofmonth', 'dayofweek')
     * accept arrays of integers for some values of 'compare'. When 'compare' is 'IN' or 'NOT IN', arrays are accepted;
     * when 'compare' is 'BETWEEN' or 'NOT BETWEEN', arrays of two valid values are required.
     * See individual argument descriptions for accepted values.
     *
     * @param array  $dateQuery {
     *     Array of date query clauses.
     *
     *     @type string $relation Optional. The boolean relationship between the date queries. Accepts 'OR' or 'AND'. Default 'OR'.
     *     @type array  ...$0 {
     *
     *         @type string       $before        Optional. Date to retrieve posts before. Accepts `strtotime()` - compatible string.
     *         @type string       $after         Optional. Date to retrieve posts after. Accepts `strtotime()` - compatible string.
     *         @type string       $column        Optional.
     *         @type string       $compare       Optional. The comparison operator. Accepts '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'.
     *                                           Comparisons support arrays in some time-related parameters. Default '='.
     *         @type int|int[]    $year          Optional. The four-digit year number. Accepts any four-digit year
     *                                           or an array of years if `$compare` supports it. Default empty.
     *         @type int|int[]    $month         Optional. The two-digit month number. Accepts numbers 1-12 or an
     *                                           array of valid numbers if `$compare` supports it. Default empty.
     *         @type int|int[]    $week          Optional. The week number of the year. Accepts numbers 0-53 or an
     *                                           array of valid numbers if `$compare` supports it. Default empty.
     *         @type int|int[]    $dayofyear     Optional. The day number of the year. Accepts numbers 1-366 or an
     *                                           array of valid numbers if `$compare` supports it.
     *         @type int|int[]    $dayofmonth    Optional. The day of the month. Accepts numbers 1-31 or an array
     *                                           of valid numbers if `$compare` supports it. Default empty.
     *         @type int|int[]    $dayofweek     Optional. The day number of the week. Accepts numbers 1-7 (1 is
     *                                           Sunday) or an array of valid numbers if `$compare` supports it.
     *                                           Default empty.
     *     }
     * }
     */
    protected static function parseDates(array $dateQuery, $table = 'pages'): string
    {
        $where = [];

        if (empty($dateQuery) || empty($table)) {
            return '';
        }

        $date        = new \DateTime();
        $table       = EX_DB_PREFIX . $table;
        $schema      = Db::schema();
        $relation    = 'OR' === strtoupper($dateQuery['relation'] ?? 'AND') ? 'OR' : 'AND';
        $comparisons = [ '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ];

        foreach ($dateQuery as $clause) {
            $comparison    = strtoupper(strval(! empty($clause['compare']) ? $clause['compare'] : '='));
            $column        = strtolower(strval(! empty($clause['column']) ? $clause['column'] : 'created'));
            $column_format = strtolower($schema[ $table ][ $column ] ?? '');

            /**
             * Check compare & db column format
             */
            if (! in_array($comparison, $comparisons, true) || $column_format !== 'datetime') {
                continue;
            }

            $fields = [
                'human_date_time' => '',
                'year'            => 'YEAR',
                'month'           => 'MONTH',
                'week'            => 'WEEK',
                'dayofyear'       => 'DAYOFYEAR',
                'dayofmonth'      => 'DAYOFMONTH',
                'dayofweek'       => 'WEEKDAY',
            ];
            foreach (array_keys($fields) as $field) {
                $the_fields = (array) ( $clause[ $field ] ?? '' );
                $callback   = ( $field === 'human_date_time' ) ? fn($v) => strtotime(strval($v)) : 'intval';
                $$field     = array_map($callback, $the_fields);

                if (! empty($clause[ $field ]) && $$field) {
                    sort($$field);

                    /**
                     * Validate & sanitize time and dates values
                     */
                    $year      = $year ?? [ intval($date->format('Y')) ];
                    $month     = $month ?? [ intval($date->format('m')) ];
                    $the_year  = intval(( $year[0] > 999 ) ? $year[0] : $date->format('Y'));
                    $the_month = intval(( $month[0] > 0 ) ? $month[0] : $date->format('m'));
                    $value     = match ($field) {
                        'human_date_time' => array_map(
                            function ($v) use ($date) {
                                return $date->setTimestamp($v)->modify('midnight')->format('Y-m-d H:i:s');
                            },
                            $human_date_time
                        ),
                        // ensure the year is between 1000 and 9999
                        'year' => array_map(
                            fn($v) => min(max($v, 1000), 9999),
                            $year
                        ),
                        // ensure the month is between 1 and 12
                        'month' => array_map(
                            fn($v) => min(max($v, 1), 12),
                            $month
                        ),
                        // ensure the week is between 1 and the number of weeks in the year.
                        'week' => array_map(
                            function ($v) use ($the_year) {
                                return min(max($v, 1), intval(( new \DateTime("$the_year-12-28") )->format('W')));
                            },
                            $week
                        ),
                        // ensure the day of the year is between 1 and the number of days in the year.
                        'dayofyear' => array_map(
                            function ($v) use ($the_year) {
                                return min(max($v, 1), (int) ( new \DateTime("$the_year-12-31") )->format('z') + 1);
                            },
                            $dayofyear
                        ),
                        // ensure the day is between 1 and the number of days in the month.
                        'dayofmonth' => array_map(
                            function ($v) use ($the_year, $the_month) {
                                return min(max($v, 1), intval(( new \DateTime("$the_year-$the_month-1") )->format('t')));
                            },
                            $dayofmonth
                        ),
                        // ensure the day of the week is between 1 and 7.
                        'dayofweek' => array_map(
                            fn($v) => min(max($v, 1), 7),
                            $dayofweek
                        ),
                    };

                    /**
                     * Builds and validates a value string based on the comparison operator.
                     */
                    if (is_array($value) && ! empty($value)) {
                        $part = sprintf("{$fields[ $field ]}%s $comparison", $field === 'human_date_time' ? "t.$column" : "(t.$column)");
                        switch ($comparison) {
                            case 'IN':
                            case 'NOT IN':
                                $where[] = sprintf("($part (%s))", implode(',', array_map('intval', $value)));
                                break;
                            case 'BETWEEN':
                            case 'NOT BETWEEN':
                                if (count($value) === 1) {
                                    $value = [ $value[0], $value[0] ];
                                }

                                $where[] = sprintf("($part %s)", implode(' AND ', array_map('intval', $value)));
                                break;
                            default:
                                $where[] = "($part '$value[0]')";
                                break;
                        }
                    }
                }
            }
        }

        if (empty($where)) {
            return '';
        }

        return sprintf('(%s)', implode(" {$relation} ", $where));
    }

    /**
     * Parse posts fields.
     *
     * @param  array  $args
     * @return string
     */
    protected static function parseFields(array $args): string
    {
        $fields = $args['fields'] ?? [];
        if (! is_array($fields) || empty($fields)) {
            return '';
        }

        $where       = [];
        $relation    = 'OR' === strtoupper($fields['relation'] ?? 'AND') ? 'OR' : 'AND';
        $comparisons = [ '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP', 'NOT REGEXP', 'LIKE', 'NOT LIKE' ];

        foreach ($fields as $field) {
            $type = strtoupper($field['type'] ?? '');
            if (! preg_match('/^(?:BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|NUMERIC(?:\(\d+(?:,\s?\d+)?\))?|DECIMAL(?:\(\d+(?:,\s?\d+)?\))?)$/', $type)) {
                $type = 'CHAR';
            }

            if ($type === 'NUMERIC') {
                $type = 'SIGNED';
            }

            $comparison = strtoupper($field['compare'] ?? '');
            $comparison = in_array($comparison, $comparisons, true) ? $comparison : '=';
            $value      = $field['value'] ?? '';
            $key        = strval($field['key'] ?? '');

            if (in_array($comparison, [ 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ], true) && ! is_array($value)) {
                $value = preg_split('/[,\s]+/', $value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }

            if (empty($key) || empty($value)) {
                continue;
            }

            switch ($comparison) {
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    if (is_array($value) && count($value) === 2) {
                        $value = implode(' AND ', array_map('strval', $value));
                    }
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                    if (is_scalar($value)) {
                        $value = "%{$value}%";
                    }
                    break;
                case 'IN':
                case 'NOT IN':
                    if (is_array($value)) {
                        $value = sprintf('(' . substr(str_repeat(',%s', count($value)), 1) . ')', ...$value);
                    }
                    break;
                case 'EXISTS':
                    $comparison = '=';
                    break;
                case 'NOT EXISTS':
                    $value = '';
                    break;
            }

            if (is_scalar($value) && ! empty($value)) {
                if ('CHAR' === $type) {
                    $where[] = "(t.key = '$key' AND t.value {$comparison} {$value})";
                } else {
                    $where[] = "(t.key = '$key' AND CAST(t.value AS {$type}) {$comparison} {$value})";
                }
            }
        }

        if (empty($where)) {
            return '';
        }

        return implode(" {$relation} ", $where);
    }

    /**
     * Parse limit for pagination.
     * TODO: optimize function
     *
     * @param  array $args
     * @return string
     */
    protected static function parseLimit(array $args): string
    {
        $default_per_page = 10;
        $page             = max(1, abs(intval($args['page'] ?? 1)));
        $offset           = max(0, abs(intval($args['offset'] ?? 0)));
        $perPage         = $args['per_page'] ?? $default_per_page;
        if (is_array($perPage)) {
            // устанавливаем значение по умолчанию, если первый элемент не существует
            $perPage = ( isset($perPage[1]) ) ? $perPage : [ 1 => $default_per_page ] + $perPage;
            ksort($perPage); // сортируем массив по ключам

            $offset += array_reduce(
                range(1, $page),
                function ($carry, $item) use ($perPage, $page) {
                    // получаем максимальный ключ, который не превышает текущей страницы
                    $max_key = max(array_filter(array_keys($perPage), fn($k) => $k <= $item));

                    // получаем количество элементов на странице
                    $value = $perPage[ $max_key ];
                    if ($item !== $page) {
                        $carry += $value;
                    }
                    return $carry;
                },
                0
            ); // считаем смещение для текущей страницы

            $max_key   = max(
                array_filter(
                    array_keys($perPage),
                    function ($key) use ($page) {
                        return $key <= $page;
                    }
                )
            ); // получаем максимальный ключ, который не превышает текущей страницы
            $perPages = $perPage[ $max_key ]; // получаем количество элементов на странице
        } else {
            $perPages = $perPage;
            $offset   += ( $page - 1 ) * $perPage; // считаем смещение для текущей страницы
        }
        $perPage = max(1, abs(intval($perPages))); // устанавливаем количество элементов на странице

        return sprintf('%d, %d', $offset, $perPage);
    }

    /**
     * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
     *
     * @param  string $order The 'order' query variable.
     * @return string        The sanitized 'order' query variable.
     */
    protected static function parseOrder($order): string
    {
        if (! is_string($order) || empty($order)) {
            return 'DESC';
        }

        return 'ASC' === strtoupper($order) ? 'ASC' : 'DESC';
    }

    /**
     * Converts the given order by alias (if allowed) to a properly-prefixed value.
     *
     * @param  string $orderby Alias for the field to order by.
     * @param  array  $args
     * @param  string $order
     * @return string          Table-prefixed value to used in the ORDER clause.
     */
    protected static function parseOrderBy(string $orderby, array $args, string $order = 'DESC'): string
    {
        if (is_array($orderby)) {
            $the_order_by = [];
            foreach ($orderby as $field => $order) {
                $the_order_by[] = self::parseOrderBy($field, $args, $order);
            }
            return implode(', ', $the_order_by);
        }

        switch ($orderby) {
            case 'ID':
            case 'author':
            case 'title':
            case 'created':
            case 'modified':
            case 'comments':
            case 'views':
                $orderby = "t.$orderby";
                break;
            case 'rand':
                $orderby = 'RAND()';
                break;
            case 'post__in':
                $post_in = $args['post__in'] ?? [];
                $orderby = 'FIELD(t.id,' . implode(',', array_map('intval', $post_in)) . ')';
                break;
            case 'parent__in':
                $parent_in = $args['parent__in'] ?? [];
                $orderby   = 'FIELD(t.parent_id,' . implode(',', array_map('intval', $parent_in)) . ')';
                break;
            default:
                $orderby = 't.created_at';
                break;
        }

        return sprintf('%s%s', $orderby, $orderby === 'RAND()' ? '' : ' ' . $order);
    }
}
