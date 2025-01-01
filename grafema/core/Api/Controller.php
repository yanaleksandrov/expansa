<?php
namespace Grafema\Api;

abstract class Controller {

	/**
	 * Endpoint
	 *
	 * @var string
	 */
	public string $endpoint = '';

	/**
	 * Root of endpoint
	 *
	 * @var string
	 */
	public string $root = '';

	/**
	 * @var array|string[]
	 */
	public array $request = [];

	/**
	 *
	 *
	 * @var string
	 */
	public string $origin = '*';

	/**
	 * Request URL
	 *
	 * @var string
	 */
	public string $url = '';

	/**
	 * Return format
	 *
	 * @var string
	 */
	protected string $format = 'json';

	/**
	 * Http methods: GET|POST|PUT|DELETE
	 *
	 * @var string
	 */
	protected string $method = '';

	/**
	 * Name of the method to execute
	 *
	 * @var string
	 */
	protected string $action = '';
}
