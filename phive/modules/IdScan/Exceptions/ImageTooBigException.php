<?php

namespace IdScan\Exceptions;

class ImageTooBigException extends \Exception
{
    public function __construct($message = null, $code = 0, \Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct('Image too Big ', $code, $previous);
    }

}