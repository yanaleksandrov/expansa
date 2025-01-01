<?php

namespace Dashboard\Forms\Traits;

trait Form {

	/**
	 * Unique ID of form class instance.
	 *
	 * @var string
	 */
	private string $uid;

	/**
	 * List of all form fields
	 *
	 * @var array
	 */
	private array $fields = [];

	/**
	 * Form default attributes
	 *
	 * @var array
	 */
	private array $attributes = [];

	/**
	 * ID of the field before which the new field will be added
	 *
	 * @var string
	 */
	private string $before = '';

	/**
	 * ID of the field after which the new field will be added
	 *
	 * @var string
	 */
	private string $after = '';

	/**
	 * ID of the field to be replaced with new field
	 *
	 * @var string
	 */
	private string $instead = '';
}
