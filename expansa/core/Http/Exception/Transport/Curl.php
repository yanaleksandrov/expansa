<?php

namespace Expansa\Http\Exception\Transport;

use Expansa\Http\Exception\Transport;

/**
 * CURL Transport Exception.
 *
 * @package Expansa\Http
 */
final class Curl extends Transport
{
    public const EASY  = 'cURLEasy';

    public const MULTI = 'cURLMulti';

    public const SHARE = 'cURLShare';

    /**
     * Create a new exception.
     *
     * @param string $reason Exception message.
     * @param string $type   Exception type of cURL error. EASY|MULTI|SHARE
     * @param mixed  $data   Associated data, if applicable.
     * @param int    $code   Exception numerical code, if applicable.
     */
    public function __construct(
        protected string $reason = 'Unknown',
        protected string $type = 'Unknown',
        mixed $data = null,
        protected $code = -1
    )
    {
        parent::__construct(sprintf('%d %s', $this->code, $this->reason), $this->type, $data, $this->code);
    }

    /**
     * Get the error message.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
