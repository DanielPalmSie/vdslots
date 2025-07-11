<?php

namespace IdScan\Exceptions;

use Exception;

class JourneyNotFoundException extends Exception
{
    // throw a custom message
    public function __construct($journeyId = null, $code = 0, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct('JourneyId not found: '.$journeyId, $code, $previous);
    }
}