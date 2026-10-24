<?php
/**
 * Term page, laid out as the post editor: its name, slug and description, the parent and details aside;
 * the data comes from App\Dashboard\Pages\Term, the fields from the `term-editor` form.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/term.blade.php
 *
 * @var App\Models\Term   $term
 * @var App\Post\Taxonomy $taxonomy
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
{!! form('term-editor') !!}
