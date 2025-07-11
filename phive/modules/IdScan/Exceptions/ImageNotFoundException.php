<?php

namespace IdScan\Exceptions;

use Exception;

class ImageNotFoundException extends Exception
{
    public function __construct($message = null, $code = 0, Exception $previous = null)
    {
        parent::__construct('Image not found', $code, $previous);
    }
}