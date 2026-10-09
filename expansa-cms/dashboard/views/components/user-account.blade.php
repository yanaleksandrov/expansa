<?php

use App\Models\User;
use Expansa\Builders\Tree;
use Expansa\Facades\Auth;

/**
 * Output user account button.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/components/user-account.php
 *
 * @package Expansa\Templates
 */
defined('EX_PATH') || exit;

$user = User::current();

ob_start();
?>
<div class="expansa-user-name"><?php echo t('Hi, :Username', $user->showname ?? ''); ?></div>
<div class="avatar avatar--xs" style="background-image: url(https://i.pravatar.cc/150?img=3)">
    <i class="badge bg-green" title="<?php echo t_attr('Online'); ?>"></i>
</div>
<?php
$label = ob_get_clean();

ob_start();
$accounts = Auth::getAccounts();
if ($accounts !== []) {
    ?>
    <ul class="user-menu">
        <li class="user-menu-divider"><?php echo t('Switch Account'); ?></li>
        <?php foreach ($accounts as $account) : ?>
            <li class="user-menu-item">
                <a class="user-menu-link" href="#" @click.prevent="$ajax.post('user/switch-account', {login: <?php echo htmlspecialchars(json_encode($account->identifier), ENT_QUOTES); ?>})">
                    <i class="ph ph-user-circle"></i> <?php echo htmlspecialchars($account->showname ?: $account->identifier); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

echo Tree::render('dashboard-user-menu', function ($items, $tree) {
    if (empty($items) || ! is_array($items)) {
        return false;
    }
    ?>
    <ul class="user-menu">
        <?php
        foreach ($items as $item) {
            ob_start();
            if (empty($item['url'])) {
                ?>
                <li class="user-menu-divider">%title$s</li>
                <?php
            } else {
                ?>
                <li class="user-menu-item">
                    <a class="user-menu-link" href="%url$s"><i class="%icon$s"></i> %title$s</a>
                </li>
                <?php
            }
            echo $tree->format(ob_get_clean(), $item);
        }
        ?>
    </ul>
    <?php
});
$content = ob_get_clean();

echo view('components/form/details', ['label' => $label, 'instruction' => '', 'content' => $content]);
