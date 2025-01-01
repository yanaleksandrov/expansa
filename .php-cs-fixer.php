<?php
/**
 * This file is part of Grafema CMS.
 *
 * @link     https://www.grafema.io
 * @contact  team@core.io
 * @license  https://github.com/grafema-team/grafema/LICENSE.md
 */
$header = <<<'EOF'
This file is part of Grafema CMS.

@link     https://www.core.io
@contact  team@core.io
@license  https://github.com/core-team/core/LICENSE.md
EOF;

return (new PhpCsFixer\Config())
	->setRiskyAllowed( true )
	->registerCustomFixers( [
		new GrafemaPHPCS\Fixer\SpaceInsideParenthesisFixer(),
	] )
	->setRules( [
		'@DoctrineAnnotation'                   => true,
		'@PSR2'                                 => true,
		'@PhpCsFixer'                           => true,
		'@Symfony'                              => true,
		'GrafemaPHPCS/space_inside_parenthesis' => true,
		'align_multiline_comment'               => true,
		'array_syntax'                          => [
			'syntax' => 'short',
		],
		'binary_operator_spaces' => [
			'operators' => [
				'=>' => 'align_single_space_minimal',
				'='  => 'align_single_space_minimal',
			],
		],
		'blank_line_before_statement' => [
			'statements' => [
				'declare',
				'return',
				'try',
				'while',
				'for',
				'foreach',
				'do',
			],
		],
		'class_attributes_separation' => true,
		'combine_consecutive_unsets'  => true,
		'concat_space'                => [
			'spacing' => 'one',
		],
		'constant_case' => [
			'case' => 'lower',
		],
		'general_phpdoc_annotation_remove' => [
			'annotations' => [
				'author',
			],
		],
		'global_namespace_import' => [
			'import_classes'   => true,
			'import_constants' => true,
			'import_functions' => true,
		],
		'header_comment' => [
			'comment_type' => 'PHPDoc',
			'header'       => $header,
			'separate'     => 'none',
			'location'     => 'after_declare_strict',
		],
		'linebreak_after_opening_tag' => true,
		'list_syntax'                 => [
			'syntax' => 'short',
		],
		'lowercase_static_reference'             => true,
		'multiline_comment_opening_closing'      => true,
		'multiline_whitespace_before_semicolons' => [
			'strategy' => 'no_multi_line',
		],
		'no_unused_imports'                 => true,
		'no_useless_else'                   => true,
		'not_operator_with_successor_space' => true,
		'ordered_class_elements'            => true,
		'ordered_imports'                   => [
			'imports_order' => [
				'class',
				'function',
				'const',
			],
			'sort_algorithm' => 'alpha',
		],
		'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
		'phpdoc_align'                        => [
			'align' => 'vertical',
			'tags'  => [
				'method',
				'param',
				'property',
				'return',
				'throws',
				'type',
				'var',
			],
		],
		'phpdoc_annotation_without_dot' => true,
		'phpdoc_no_alias_tag'           => true,
		'phpdoc_separation'             => true,
		'phpdoc_no_useless_inheritdoc'  => false,
		'single_line_comment_style'     => [
			'comment_types' => [],
		],
		'single_quote'           => true,
		'standardize_not_equals' => true,
		'yoda_style'             => [
			'always_move_variable' => false,
			'equal'                => false,
			'identical'            => false,
		],
	] )
	->setIndent( '	' )
	->setFinder(
		PhpCsFixer\Finder::create()
			->exclude( 'docs' )
			->exclude( 'vendor' )
			->exclude( 'src' )
			->in( __DIR__ )
	)
	->setUsingCache( false );
