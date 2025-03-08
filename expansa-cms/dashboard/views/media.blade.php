<?php

use Expansa\Facades\Hook;

/**
 * Files storage template can be overridden by copying it to themes/yourtheme/dashboard/views/media.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Media();
$column = $table->cells[0] ?? [];

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/media-editor');
    echo view('dialogs/media-uploader');
});
?>
<?php echo view('table/header', $table->headData()); ?>

@if($table->data)
    <div class="storage" x-storage>
        @foreach($table->data as $item)
            <div class="storage__item">
                <?php echo view($column->view, $item); ?>
            </div>
        @endforeach
    </div>
@else
    <?php echo view('global/state', $table->notFoundData()); ?>
@endif

<div x-intersect="$ajax('media/get').then(({posts}) => items = posts)"></div>
