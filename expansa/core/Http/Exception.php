<?php

namespace Expansa\Http;

use Exception as PHPException;

/**
 * Exception for HTTP requests
 *
 * @package Requests\Exceptions
 */
class Exception extends PHPException
{
    /**
     * Create a new exception
     *
     * @param string $message Exception message
     * @param string $type    Exception type
     * @param mixed  $data    Data associated with the exception
     * @param int    $code    Exception numerical code, if applicable
     */
    public function __construct(
        string $message,
        protected string $type,
        protected mixed $data = null,
        int $code = 0
    )
    {
        parent::__construct($message, $code);
    }

    /**
     * Like {@see \Exception::getCode()}, but a string code.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gives any relevant data
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
