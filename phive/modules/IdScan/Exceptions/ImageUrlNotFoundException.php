<?php

namespace IdScan\Exceptions;

use Exception;

class ImageUrlNotFoundException extends Exception
{
    // throw a custom message
    public function __construct($message = null, $code = 0, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct('Image URL not found', $code, $previous);
    }
}