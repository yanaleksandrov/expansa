<?php
/**
 * This file is part of Expansa CMS.
 *
 * @link     https://www.expansa.io
 * @contact  team@core.io
 * @license  https://github.com/expansa-team/expansa/LICENSE.md
 */
if (!defined('EX_PATH')) {
    exit;
}
?>
<div class="expansa-main">
    <?php (new \Expansa\Builders\Table(new \app\Tables\Plugins()))->print(); ?>
</div>
