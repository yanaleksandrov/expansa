<?php

declare(strict_types=1);

namespace Expansa\Api;

class Response
{
    public static function getStatus()
    {
        return http_response_code();
    }

    public static function setStatus(int $code)
    {
        http_response_code($code);
    }

    public static function get(int $code): array
    {
        $description = match ($code) {
            200 => 'OK: The request has been successfully fulfilled, and the server is returning the requested data.',
            201 => 'Created: The request has been successfully fulfilled, and a new resource has been created as a result.',
            202 => 'Accepted: The request has been accepted for processing but has not been completed yet.',
            203 => 'Non-Authoritative Information: The returned meta-information is not from the original server but from a local or third-party copy.',
            204 => 'No Content: The request has been successfully fulfilled, but there is no content to be returned.',
            400 => 'Bad Request: The server cannot process the request due to invalid syntax or malformed request.',
            401 => 'Unauthorized: Authentication is required to access the requested resource.',
            402 => 'Payment Required: Reserved for future use.',
            403 => 'Forbidden: The server understood the request but refuses to authorize it.',
            404 => 'Not Found: The requested resource could not be found on the server.',
            405 => 'Method Not Allowed: The method specified in the request is not allowed for the requested resource.',
            406 => 'Not Acceptable: The server cannot produce a response matching the list of acceptable values defined in the request headers.',
            407 => 'Proxy Authentication Required: Authentication with a proxy server is required.',
            408 => 'Request Timeout: The server timed out waiting for the request.',
            409 => 'Conflict: The request could not be completed due to a conflict with the current state of the resource.',
            410 => 'Gone: The requested resource is no longer available and has been permanently removed.',
            411 => 'Length Required: The server requires a valid Content-Length header to be specified in the request.',
            412 => 'Precondition Failed: One or more conditions specified in the request headers are not met.',
            413 => 'Payload Too Large: The request payload exceeds the maximum size limit allowed by the server.',
            414 => 'URI Too Long: The length of the request URI exceeds the maximum limit allowed by the server.',
            415 => 'Unsupported Media Type: The server does not support the media type specified in the request.',
            416 => 'Range Not Satisfiable: The requested range of a resource cannot be fulfilled by the server.',
            500 => 'Internal Server Error: The server encountered an unexpected condition that prevented it from fulfilling the request.',
            501 => 'Not Implemented: The server does not support the functionality required to fulfill the request.',
            502 => 'Bad Gateway: The server was acting as a gateway or proxy and received an invalid response from an upstream server.',
            503 => 'Service Unavailable: The server is currently unable to handle the request due to a temporary overload or maintenance of the server.',
            504 => 'Gateway Timeout: The server was acting as a gateway or proxy and did not receive a timely response from an upstream server.',
            505 => 'HTTP Version Not Supported: The server does not support the HTTP protocol version used in the request.',
            506 => 'Variant Also Negotiates: Transparent content negotiation for the request results in a circular reference.',
            507 => 'Insufficient Storage: The server is unable to store the representation needed to complete the request.',
            508 => 'Loop Detected: The server detected an infinite loop while processing the request.',
            510 => 'Not Extended: Further extensions to the request are required for the server to fulfill it.',
            511 => 'Network Authentication Required: The client needs to authenticate to gain network access.',
        };

        return [
            'success'     => $code >= 200 && $code < 400,
            'response'    => $code,
            'description' => $description,
        ];
    }
}
