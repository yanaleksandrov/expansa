<?php
/**
 * Translation table template can be overridden by copying it to themes/yourtheme/dashboard/views/translation.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
    exit;
}

$table = new App\Tables\Translations();
$items = $table->data ?? [];
$cells = $table->columns ?? [];
?>
<div class="expansa-main">
    <?php
    echo view('table/header', [
        'title'       => t('Translations'),
        'badge'       => t('completed :stringsCount from :allStringsCount <i class="t-green">(:percent%)</i>', 56, 408, 25),
        'translation' => true,
    ]);
    echo '<pre>';
    print_r($table);
    echo '</pre>';
    ?>

    @if($items)
        <form class="table translation" method="POST" @input.debounce.500ms="$ajax('translations/update',{project})">
            <div class="table__head" style="{{ $table->stylize($cells) }}">
                @foreach($cells as $cell)
                    <?php echo view('table/cell-head', ['cell' => $cell]); ?>
                @endforeach
            </div>
            @foreach($items as $item)
                <div class="table__row" style="{{ $table->stylize($cells) }}">
                    @foreach($cells as $cell)
                        <?php echo view($cell->view, ['class' => $cell->key, ...$item]); ?>
                    @endforeach
                </div>
            @endforeach
        </form>
    @else
        <?php
        echo view('global/state', [
            'title'       => t('Translates not found'),
            'description' => t("Click the 'Scan' button to get started and load the strings to be translated from the source code."),
        ]);
        ?>
    @endif
</div>
