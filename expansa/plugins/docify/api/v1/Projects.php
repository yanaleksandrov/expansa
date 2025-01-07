<?php

namespace Docify\Api\v1;

use Expansa;

class Projects extends Expansa\Api\Handler
{
	public string $endpoint = 'projects';

	/**
	 * Get all items.
	 *
	 * @url    GET api/projects
	 */
	public function index(): array
	{
		return [
			'method' => 'GET',
		];
	}

	/**
	 * Create item.
	 *
	 * @url    POST api/projects
	 */
	public function create(): array
	{
		return [
			'method' => 'GET',
		];
	}

	/**
	 * Update item by ID.
	 *
	 * @url    POST api/projects/$id
	 */
	public function update(): array
	{
		return [
			'method' => 'GET',
		];
	}

	/**
	 * Remove item by ID.
	 *
	 * @url    DELETE api/projects/$id
	 */
	public function delete(): array
	{
		return [
			'method' => 'GET',
		];
	}
}
