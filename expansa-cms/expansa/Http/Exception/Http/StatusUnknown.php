<?php

namespace Expansa\Http\Exception\Http;

use Expansa\Http\Exception\HttpStatuses;
use Expansa\Http\Response;

/**
 * Exception for unknown status responses
 *
 * @package Requests\Exceptions
 */
final class StatusUnknown extends HttpStatuses
{
    /**
     * HTTP status code
     *
     * @var int|bool Code if available, false if an error occurred
     */
    protected $code = 0;

    /**
     * Reason phrase
     *
     * @var string
     */
    protected $reason = 'Unknown';

    /**
     * Create a new exception
     *
     * If `$data` is an instance of Response, uses the status code from it. Otherwise, sets as 0
     *
     * @param string|null $reason Reason phrase
     * @param mixed       $data   Associated data
     */
    public function __construct(?string $reason = null, $data = null)
    {
        if ($data instanceof Response) {
            $this->code = (int) $data->statusCode;
        }

        parent::__construct($reason, $data);
    }
}
