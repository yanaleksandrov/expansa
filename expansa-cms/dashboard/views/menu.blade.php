<?php
/**
 * Expansa dashboard menu.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/menu.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-side">
<?php
    echo tree('dashboard-main-menu', $test = function ($items, $tree) use (&$test) {
        if (empty($items) || !is_array($items)) {
            return false;
        }

        $id    = strval($items[0]['parent_id'] ?? '');
        $depth = intval($items[0]['depth'] ?? 0);
        $class = $depth === 0 ? 'class="nav" x-data="{i:\'' . $id . '\'}" x-sticky' : 'class="nav__list" x-show="i === \'' . $id . '\'" x-collapse x-cloak';
        ?>
        <ul <?php echo $class; ?>>
            <?php
            foreach ($items as $item) {
                ob_start();
                if (empty($item['url'])) {
                    ?>
                    <li class="nav__item nav__item--divider">%title$s</li>
                    <?php
                } elseif ($item['depth'] > 0) {
                    ?>
                    <li><a class="nav__link" href="%url$s">%title$s</a></li>
                    <?php
                } elseif (empty($item['children'])) {
                    ?>
                    <li class="nav__item">
                        <a class="nav__link" href="%url$s"><i class="%icon$s"></i> %title$s</a>
                    </li>
                    <?php
                } else {
                    $id = (string)($item['id'] ?? '');
                    ?>
                    <li class="nav__item nav__item--parent">
                        <a class="nav__link" href="%url$s" @click.prevent="i = '<?php echo $id; ?>'">
                            <i class="%icon$s"></i> %title$s
                                <?php if (isset($item['count'])) { ?>
                            <span class="badge badge--blue-lt ml-auto">%count$d</span>
                            <?php } ?>
                        </a>
                        <?php $test($item['children'] ?? [], $tree); ?>
                    </li>
                    <?php
                }
                    echo $tree->vsprintf(ob_get_clean(), $item);
                }
            ?>
        </ul>
      <?php
    });
?>
</div>
