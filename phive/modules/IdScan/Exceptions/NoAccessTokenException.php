<?php

namespace IdScan\Exceptions;
use Exception;

class NoAccessTokenException extends Exception
{
    // throw a custom message
    public function __construct($message = null, $code = 0, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct('Error getting IdScan access token', $code, $previous);
    }
}