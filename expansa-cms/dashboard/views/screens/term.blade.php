<?php
/**
 * Term page: its name, slug, parent and description; the data comes from App\Dashboard\Pages\Term,
 * the fields from the `terms-editor` form.
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
<div class="term-editor">
    {!! form('terms-editor') !!}
</div>
