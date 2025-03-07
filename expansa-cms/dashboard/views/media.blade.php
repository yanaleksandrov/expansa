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

$table  = new App\Tables\Media();
$items  = $table->data ?? [];
$cells  = $table->columns ?? [];
$column = $table->columns[0] ?? [];

Hook::add('expansa_dashboard_footer', function () {
    echo view('dialogs/media-editor');
    echo view('dialogs/media-uploader');
});
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'    => t('Media Library'),
        'actions'  => false,
        'filter'   => false,
        'uploader' => true,
        'show'     => 'false',
        'content'  => '',
    ]);
    ?>
    @if($items)
        <div class="storage" x-storage>
            @foreach($items as $item)
                <div class="storage__item">
                    <?php echo view($column->view, $item); ?>
                </div>
            @endforeach
        </div>
    @else
        <?php
        echo view('global/state', [
            'icon'        => 'no-media',
            'title'       => t('Files in library is not found'),
            'description' => t('They have not been uploaded or do not match the filter parameters'),
        ]);
        ?>
    @endif
    <div x-intersect="$ajax('media/get').then(({posts}) => items = posts)"></div>
</div>
