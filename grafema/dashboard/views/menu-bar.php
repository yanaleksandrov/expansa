<?php
use Grafema\Tree;
use Grafema\Sanitizer;

/**
 * Grafema dashboard menu.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/menu-bar.php
 *
 * @package Grafema\Templates
 * @since   2025.1
 */
if ( ! defined( 'GRFM_PATH' ) ) {
	exit;
}

Tree::view( 'dashboard-menu-bar', function ( $items ) {
    ?>
    <ul id="dashboard-menu-bar" class="menu mr-auto">
        <?php
        foreach ( $items as $item ) :
			[ $url, $icon, $title ] = (
                new Sanitizer(
					$item,
                    [
                        'url'   => 'url',
                        'icon'  => 'class',
                        'title' => 'trim',
                    ]
                )
			)->values();
			?>
            <li class="menu__item">
                <a class="menu__link" href="<?php echo $url; ?>"><?php $icon && printf( '<i class="%s"></i>', $icon ); ?> <?php echo $title; ?></a>
            </li>
			<?php
        endforeach;
    ?>
    </ul>
    <?php
} );
