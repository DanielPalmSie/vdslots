<?php

namespace IdScan\Exceptions;

class NotReadableImageException extends \Exception
{
    // throw a custom message
    public function __construct($message = null, $code = 0, \Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct('Image not readable: ' . $message, $code, $previous);
    }
}
