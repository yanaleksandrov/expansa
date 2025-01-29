<?php

use app\User;
use Expansa\Builders\Tree;
use Expansa\I18n;
use Expansa\Safe;

/**
 * Output user account button.
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/global/user-account.php
 *
 * @package Expansa\Templates
 */
if (!defined('EX_PATH')) {
	exit;
}

[$class, $rating, $reviews] = Safe::data(
	$__data ?? [],
	[
		'class' => 'class:df aic g-1',
		'rating' => 'float',
		'reviews' => 'absint',
	]
)->values();

$user = User::current();

ob_start();
?>
<div class="expansa-user-name"><?php echo t('Hi, :Username', $user->showname ?? ''); ?></div>
<div class="avatar avatar--xs" style="background-image: url(https://i.pravatar.cc/150?img=3)">
	<i class="badge bg-green" title="<?php I18n::c_attr( true, 'Online', 'Offline' ); ?>"></i>
</div>
<?php
$label = ob_get_clean();

echo view('form/details', [
    'label'       => $label,
	'instruction' => '',
	'content'     => Tree::include('dashboard-user-menu', $test = function ($items, $tree) use (&$test) {
		if (empty($items) || !is_array($items)) {
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
				echo $tree->vsprintf(ob_get_clean(), $item);
			}
			?>
		</ul>
		<?php
	}),
]);
