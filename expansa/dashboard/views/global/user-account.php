<?php

use Expansa\Safe;
use Expansa\View;
use Expansa\Tree;
use Expansa\User;
use Expansa\I18n;

/**
 * Output user account button.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/global/user-account.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}

[ $class, $rating, $reviews ] = Safe::data(
	$args ?? [],
	[
		'class'   => 'class:df aic g-1',
		'rating'  => 'float',
		'reviews' => 'absint',
	]
)->values();

$user = User::current();

ob_start();
?>
<div class="expansa-user-name"><?php I18n::t( 'Hi, :Username', $user->showname ); ?></div>
<div class="avatar avatar--xs" style="background-image: url(https://i.pravatar.cc/150?img=3)">
	<i class="badge bg-green" title="<?php I18n::c_attr( true, 'Online', 'Offline' ); ?>"></i>
</div>
<?php
$label = ob_get_clean();

View::print(
	'views/form/details',
	[
		'label'       => $label,
		'instruction' => '',
		'content'     => Tree::include(
			'dashboard-user-menu',
			$test = function ( $items, $tree ) use ( &$test ) {
				if ( empty( $items ) || ! is_array( $items ) ) {
					return false;
				}
				?>
				<ul class="user-menu">
					<?php
					foreach ( $items as $item ) {
						ob_start();
						if ( empty( $item['url'] ) ) {
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
						echo $tree->vsprintf( ob_get_clean(), $item );
					}
					?>
				</ul>
				<?php
			}
		),
	]
);
