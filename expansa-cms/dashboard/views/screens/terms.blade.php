<?php
/**
 * Terms of a taxonomy: the form of a new term aside, the list of the terms with its header; the data comes
 * from App\Dashboard\Pages\Tables, the fields from the `terms-editor` form.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/terms.blade.php
 *
 * @var App\Tables\Terms $table
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;
?>
<div class="terms">
    <div class="terms-side">
        {!! form('terms-editor') !!}
    </div>
    <div class="terms-main">
        <?php echo view('parts/table/table', ['table' => $table]); ?>

        @if ($table->taxonomy->hierarchical)
            <p class="terms-note">{{ t('Deleting a term does not delete the posts that have it. Posts left without terms get the default one, which cannot be deleted.') }}</p>
        @endif
    </div>
</div>
