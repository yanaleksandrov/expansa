<?php

use Expansa\View;
use Dashboard\Form;

/**
 * Attributes list
 *
 * This template can be overridden by copying it to themes/yourtheme/ecommerce/templates/attributes.php
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
					<span class="fw-600 mr-auto"><?php echo t( 'Attributes' ); ?></span>
					<button class="btn btn--sm" type="button"><?php echo t( 'Export' ); ?></button>
					<button class="btn btn--sm" type="button"><?php echo t( 'Import' ); ?></button>
					<button class="btn btn--sm btn--primary" type="submit"><?php echo t( 'Save' ); ?></button>
				</h5>
				<div class="attributes-description">
					<p><?php echo t( 'Attributes define product details such as size or color and allow them to be included in product filtering.' ); ?></p>
				</div>
				<?php Form::print( EX_PLUGINS . 'ecommerce/core/attributes.php', true ); ?>
			</div>
			<div class="attributes-side">
				<div x-text="`<?php echo t_attr( ':attributesCount items', '${attributes.length}' ); ?>`">0 items</div>
				<div class="attributes-list">
					<div class="attributes-values">
						<template x-if="attributes.length">
							<template x-for="(value, i) in attributes" :key="i">
								<a class="attributes-value">
									<span class="attributes-value-title" x-text="`attributes.${i}.title`"></span>
									<span class="attributes-value-slug" x-text="`attributes.${i}.slug`"></span>
									<div class="btn btn--icon" @click="attributes.splice(i, 1)"><i class="ph ph-pen"></i></div>
								</a>
							</template>
						</template>
						<template x-if="!attributes.length">
							<?php
							echo view(
								'global/state',
								[
									'icon'        => 'empty-pack',
									'class'       => 'dg jic m-auto t-center p-8 mw-320',
									'title'       => t( 'Attributes not found' ),
									'description' => t( 'Try to add new attribute, there will be results here' ),
								]
							);
							?>
						</template>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
