<?php

use App\Models\Option;
use App\Support\Transliteration;

/**
 * Permalinks settings in dashboard
 *
 * @since 2027.1
 */

$mode    = Option::get( 'permalinks.transliterate', false ) ? 'latin' : 'keep';
$scripts = [
	'latin'    => [
		'label'  => t( 'Latin' ),
		'lang'   => '',
		'dir'    => 'ltr',
		'groups' => [
			'letters' => t( 'Letters with diacritics' ),
		],
	],
	'cyrillic' => [
		'label'  => t( 'Cyrillic' ),
		'lang'   => 'ru',
		'dir'    => 'ltr',
		'groups' => [
			'letters'  => t( 'Letters' ),
			'extended' => t( 'Letters of other languages' ),
		],
	],
	'arabic'   => [
		'label'  => t( 'Arabic' ),
		'lang'   => 'ar',
		'dir'    => 'rtl',
		'groups' => [
			'letters'  => t( 'Letters' ),
			'extended' => t( 'Letters of other languages' ),
			'marks'    => t( 'Diacritics' ),
			'digits'   => t( 'Digits' ),
		],
	],
	'hebrew'   => [
		'label'  => t( 'Hebrew' ),
		'lang'   => 'he',
		'dir'    => 'rtl',
		'groups' => [
			'letters'  => t( 'Letters' ),
			'finals'   => t( 'Final forms' ),
			'extended' => t( 'Yiddish ligatures' ),
			'marks'    => t( 'Vowel points' ),
		],
	],
	'hangul'   => [
		'label'  => t( 'Hangul' ),
		'lang'   => 'ko',
		'dir'    => 'ltr',
		'groups' => [
			'initials' => t( 'Initial consonants' ),
			'vowels'   => t( 'Vowels' ),
			'finals'   => t( 'Final consonants' ),
		],
	],
];

$options = [
	'keep'  => [
		'content'     => t( 'Keep the letters' ),
		'icon'        => 'ph ph-globe-hemisphere-west',
		'description' => t( 'Slugs keep the letters of any script, e.g. :example', '<code>' . url( '/привет-мир/' ) . '</code>' ),
		'checked'     => $mode === 'keep',
	],
	'latin' => [
		'content'     => t( 'Transliterate to Latin' ),
		'icon'        => 'ph ph-translate',
		'description' => t( 'The letters of all scripts below are replaced with their values, e.g. :example', '<code>' . url( '/privet-mir/' ) . '</code>' ),
		'checked'     => $mode === 'latin',
	],
];

// letters of a script with the Latin inputs; diacritics are shown on a dotted circle
$letters = static fn ( string $name, array $config ): Closure => static function () use ( $name, $config ) {
	$defaults = Transliteration::SCRIPTS[ $name ];
	foreach ( Transliteration::letters( $name ) as $group => $values ) :
		$combining = $group === 'marks';
		?>
		<div class="dg g-2">
			<div class="field-label"><?php echo $config['groups'][ $group ] ?? $group; ?></div>
			<div class="field transliteration">
				<?php foreach ( $values as $letter => $latin ) : ?>
					<label class="field-item" title="<?php echo t_attr( 'Default: :value', $defaults[ $group ][ $letter ] ?: '—' ); ?>">
						<span class="transliteration-letter" lang="<?php echo $config['lang']; ?>" dir="<?php echo $config['dir']; ?>"><?php echo ( $combining && preg_match( '/^\p{M}/u', $letter ) ? '◌' : '' ) . $letter; ?></span>
						<input type="text" name="permalinks[scripts][<?php echo $name; ?>][<?php echo $group; ?>][<?php echo $letter; ?>]" value="<?php echo $latin; ?>" maxlength="8" pattern="[a-z0-9]*" autocomplete="off" spellcheck="false">
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	endforeach;
};

$groups = [];
foreach ( $scripts as $name => $config ) {
	$groups[] = [
		'type'          => 'group',
		'name'          => "transliteration-$name",
		'label'         => $config['label'],
		'class'         => '',
		'label_class'   => '',
		'content_class' => 'dg ga-4 g-6 gtc-1',
		'conditions'    => [
			[
				'field'    => 'permalinks[mode]',
				'operator' => '==',
				'value'    => 'latin',
			],
		],
		'fields'        => [
			[
				'name'     => "permalinks-$name",
				'type'     => 'custom',
				'callback' => $letters( $name, $config ),
			],
		],
	];
}

return Expansa\Builders\Form::enqueue(
	'permalinks',
	[
		'class'           => 'tab',
		'u-data'          => 'tab',
		'@load'           => '$dirty.watch($el)',
		'@submit.prevent' => '$ajax.post("options/update", "", () => $dirty.remove($el))',
	],
	[
		[
			'name'        => 'permalinks',
			'type'        => 'tab',
			'label'       => t( 'Permalinks' ),
			'caption'     => t( 'URL Structure' ),
			'description' => t( 'Custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links.' ),
			'icon'        => 'ph ph-link',
			'fields'      => [
				[
					'type'          => 'group',
					'name'          => 'dates',
					'label'         => t( 'Pages' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][single]',
							'error'       => 'permalinks.pages.single',
							'label'       => t( 'Single Page' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code><i class="ph ph-link"></i> %s</code>', url() ),
							'after'       => '',
							'instruction' => t( 'Select the permalink structure for your website. Including the %slug% tag makes links easy to understand, and can help your posts rank higher in search engines.' ),
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'permalinks.pages.single',
								'value'    => Option::get( 'permalinks.pages.single' ),
								'required' => true,
							],
						],
						[
							'type'        => 'text',
							'name'        => 'permalinks[pages][categories]',
							'error'       => 'permalinks.pages.categories',
							'label'       => t( 'Categories' ),
							'class'       => '',
							'label_class' => '',
							'reset'       => 0,
							'before'      => sprintf( '<code><i class="ph ph-link"></i> %s</code>', url() ),
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'u-prop' => 'permalinks.pages.categories',
								'value'    => Option::get( 'permalinks.pages.categories' ),
								'required' => true,
							],
						],
					],
				],
			],
		],
		[
			'name'        => 'transliteration',
			'type'        => 'tab',
			'label'       => t( 'Transliteration' ),
			'caption'     => t( 'Slug Transliteration' ),
			'description' => t( 'Slugs are made in Latin letters: each letter is replaced with its value. A saved slug changes when its entry is saved again.' ),
			'icon'        => 'ph ph-link-simple',
			'fields'      => [
				[
					'type'          => 'group',
					'name'          => 'mode',
					'label'         => t( 'Mode' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
						[
							'type'        => 'radio',
							'name'        => 'permalinks[mode]',
							'error'       => 'permalinks.mode',
							'label'       => t( 'Mode' ),
							'class'       => 'field field--ui',
							'label_class' => '',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value' => $mode,
							],
							'options'     => $options,
						],
					],
				],
				[
					'type'          => 'group',
					'name'          => 'rules',
					'label'         => t( 'Own Rules' ),
					'class'         => '',
					'label_class'   => '',
					'content_class' => 'dg ga-4 g-6 gtc-1',
					'fields'        => [
						[
							'name'     => 'permalinks-rules',
							'type'     => 'custom',
							'callback' => static function () {
								$rules = [];
								foreach ( (array) Option::get( 'permalinks.rules', [] ) as $from => $to ) {
									$rules[] = [
										'from' => (string) $from,
										'to'   => (string) $to,
									];
								}
								?>
								<div class="dg g-2" u-data="{ rules: <?php echo htmlspecialchars( json_encode( $rules, JSON_UNESCAPED_UNICODE ), ENT_QUOTES ); ?> }">
									<div class="dg g-2">
										<div class="df aic g-2" u-each="(rule, i) in rules">
											<label class="field field-item">
												<input type="text" name="permalinks[rules][from][]" u-prop="rule.from" placeholder="<?php echo t_attr( 'Text' ); ?>" autocomplete="off" spellcheck="false">
											</label>
											<i class="ph ph-arrow-right t-muted"></i>
											<label class="field field-item">
												<input type="text" name="permalinks[rules][to][]" u-prop="rule.to" placeholder="<?php echo t_attr( 'Replacement' ); ?>" pattern="[a-z0-9\-]*" autocomplete="off" spellcheck="false">
												<i class="ph ph-trash-simple t-red" @click="rules = rules.filter((removed, index) => index !== i)" title="<?php echo t_attr( 'Remove' ); ?>"></i>
											</label>
										</div>
									</div>
									<div class="field-instruction"><?php echo t( 'Any text and its replacement in Latin letters, digits or hyphens, e.g. :from to :to. Own rules go before the tables of the scripts and also work when the letters are kept; a longer text wins.', '<code>&amp;</code>', '<code>and</code>' ); ?></div>
									<button type="button" class="btn btn--outline" @click="rules = [...rules, { from: '', to: '' }]">
										<i class="ph ph-plus"></i> <?php echo t( 'Add Rule' ); ?>
									</button>
								</div>
								<?php
							},
						],
					],
				],
				...$groups,
			],
		],
		[
			'type'     => 'custom',
			'callback' => function () {
				?>
				<div class="expansa-form-actions">
					<div class="expansa-form-actions-caption">
						<i class="ph ph-warning-circle"></i> <?php echo t( 'Unsaved changes' ); ?>
					</div>
					<div class="expansa-form-actions-buttons">
						<button class="btn t-red" type="reset"><?php echo t( 'Discard' ); ?></button>
						<button class="btn t-green" type="submit"><?php echo t( 'Save' ); ?></button>
					</div>
				</div>
				<?php
			},
		],
	]
);
