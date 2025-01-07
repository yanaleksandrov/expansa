<?php

use Dashboard\Form;
use Expansa\I18n;
use Expansa\Url;

/**
 * Single order data.
 *
 * This template can be overridden by copying it to themes/yourtheme/ecommerce/templates/order.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
?>
<div class="expansa-main">
	<div class="attributes">
		<form class="attributes-wrapper" x-data="{attributes: []}">
			<div class="attributes-editor">
				<h5 class="attributes-title">
					<a class="btn btn--icon btn--sm" href="<?php echo Url::dashboard( '/orders' ); ?>"><i class="ph ph-arrow-left"></i></a>
					<span class="fw-600 mr-auto"><?php I18n::t( 'Order :orderNumber details', '#10566' ); ?></span>
					<button class="btn btn--sm btn--primary" type="submit"><?php I18n::t( 'Save' ); ?></button>
				</h5>
				<div class="attributes-description">
					<p>Updated by Ian Iskenderov December 23, 10:14 pm</p>
				</div>
				<?php Form::print( EX_PLUGINS . 'ecommerce/core/order.php', true ); ?>
			</div>
			<div class="attributes-side">
				<div><?php I18n::t( 'Timeline' ); ?></div>
				<div class="notes">
					<div class="notes-item">
						<abbr class="notes-item-abbr" title="2024-12-16 00:46:36">16.12.2024 at 00:46</abbr> by Ian Aleksandrov
						<div class="notes-item-data">
							<p>Order status changed from Processing to Completed.</p>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
