<?php
use Grafema\I18n;
use Grafema\Url;
use Grafema\Sanitizer;

/**
 * Form for create & edit posts.
 *
 * @since 2025.1
 */
?>
<div class="dg g-6 gtc-12">
	<div class="dg ga-8">
		<?php
		echo Dashboard\Form::build(
			[
				[
					'type'        => 'textarea',
					'name'        => 'title',
					'label'       => '',
					'class'       => '',
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
						'rows'        => 1,
						'required'    => true,
						'placeholder' => I18n::_t( 'Add title...' ),
					],
				],
				[
					'type'        => 'text',
					'name'        => 'permalink',
					'label'       => '',
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'     => sprintf( '<code class="badge">%s</code>', Url::site() ),
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'required' => true,
					],
				],
				[
					'type'        => 'textarea',
					'name'        => 'excerpt',
					'label'       => '',
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => I18n::_t( 'This section only applicable to post types that have excerpts enabled. Here you can write a one to two sentence description of the post.' ),
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'rows'        => 1,
						'value'       => '',
						'placeholder' => I18n::_t( 'Write an excerpt (optional)...' ),
					],
				],
			]
		);
		?>
	</div>
	<div class="dg ga-4">
		<?php
		echo Dashboard\Form::build(
			[
				[
					'type'        => 'select',
					'name'        => 'status',
					'label'       => '',
					'class'       => '',
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
						'value' => 'publish',
					],
					'options' => [
						'publish' => I18n::_t( 'Publish' ),
						'pending' => I18n::_t( 'Pending' ),
						'draft'   => I18n::_t( 'Draft' ),
					],
				],
				[
					'type'        => 'select',
					'name'        => 'visibility',
					'label'       => '',
					'class'       => '',
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
						'value' => 'public',
					],
					'options' => [
						'public'  => I18n::_t( 'Public' ),
						'private' => I18n::_t( 'Private' ),
						'pending' => I18n::_t( 'Password protected' ),
					],
				],
				[
					'type'        => 'date',
					'name'        => 'from',
					'label'       => '',
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => I18n::_f( '%sFrom:%s', '<samp class="badge badge--blue-lt">', '</samp>' ),
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder' => I18n::_t( 'e.g. Just another Grafema site' ),
					],
				],
				[
					'type'        => 'date',
					'name'        => 'to',
					'label'       => '',
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => I18n::_f( '%sTo:%s', '<samp class="badge badge--blue-lt">', '</samp>' ),
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'placeholder' => I18n::_t( 'e.g. Just another Grafema site' ),
					],
				],
				[
					'type'        => 'select',
					'name'        => 'language',
					'label'       => '',
					'class'       => '',
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
						'value'    => 'us',
						'required' => true,
					],
					'options' => [
						'us' => [
							'image'   => 'assets/images/flags/us.svg',
							'content' => I18n::_t( 'English - english' ),
						],
						'ru' => [
							'image'   => 'assets/images/flags/ru.svg',
							'content' => I18n::_t( 'Russian - русский' ),
						],
						'he' => [
							'image'   => 'assets/images/flags/il.svg',
							'content' => I18n::_t( 'עִבְרִית - Hebrew' ),
						],
					],
				],
				[
					'type'        => 'select',
					'name'        => 'discussion',
					'label'       => '',
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [],
					'options' => [
						'open'        => I18n::_t( 'Open' ),
						'close'       => I18n::_t( 'Close' ),
						'temporarily' => I18n::_t( 'Temporarily' ),
					],
				],
			]
		);
		?>
	</div>
</div>
