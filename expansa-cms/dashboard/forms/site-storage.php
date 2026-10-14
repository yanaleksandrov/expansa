<?php

use App\Models\Option;

/**
 * Website file storage in dashboard
 *
 * @since 2027.1
 */

return Expansa\Builders\Form::enqueue(
	'site-storage',
	[
		'class'           => 'tab',
		'u-data'          => 'tab',
		'@load'           => '$dirty.watch($el)',
		'@submit.prevent' => '$ajax.post("options/update", "", () => $dirty.remove($el))',
	],
	[
		[
			'name'    => 'storage',
			'type'    => 'tab',
			'label'   => t( 'Storage' ),
			'caption' => t( 'Media Options' ),
			'icon'    => 'ph ph-lockers',
			'fields'  => [
				[
					'type'          => 'group',
					'name'          => 'images',
					'label'         => t( 'File Uploads' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
						[
							'type'        => 'select',
							'name'        => 'images[format]',
							'error'       => 'images.format',
							'label'       => t( 'Convert Images To' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.format == \'' . Option::get( 'images.format' ) . '\'">Convert existing images</button>',
							'instruction' => t( 'Changing this setting only affects newly uploaded images. Existing images keep their current format.' ),
							'tooltip'     => t( 'May reduce image detail and quality, and increase your hosting costs' ),
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'images.format',
								'value' => Option::get( 'images.format' ),
							],
							'options'     => [
								''     => t( 'Do not convert' ),
								'wepb' => 'WebP',
								'avif' => 'Avif',
							],
						],
						[
							'type'        => 'select',
							'name'        => 'images[organization]',
							'error'       => 'images.organization',
							'label'       => t( 'File Organization' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '<button type="button" class="btn btn--xs btn--primary" @click="" :disabled="images.organization.trim() == \'' . Option::get( 'images.organization', 'yearmonth' ) . '\'">Convert existing files</button>',
							'instruction' => t( 'Changing this setting only affects new files. Existing files stay where they are.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'images.organization',
							],
							'options'     => [
								'yearmonth' => t( 'Into month- and year-based folders' ),
								'hash'      => t( 'Into hash-based folders' ),
							],
						],
					],
				],
			],
		],
        [
            'type'     => 'custom',
            'callback' => function () {
                ?>
				<div class="expansa-form-actions">
					<div class="expansa-form-actions-caption">
						<i class="ph ph-warning-circle"></i> <?php echo t('Unsaved changes'); ?>
					</div>
					<div class="expansa-form-actions-buttons">
						<button class="btn t-red" type="reset"><?php echo t('Discard'); ?></button>
						<button class="btn t-green" type="submit"><?php echo t('Save'); ?></button>
					</div>
				</div>
                <?php
            },
        ],
	]
);