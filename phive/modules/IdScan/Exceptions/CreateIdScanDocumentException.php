<?php

namespace IdScan\Exceptions;

class CreateIdScanDocumentException extends \Exception
{

    public function __construct($message = null, $code = 0, \Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct($message ?? 'Error creating document', $code, $previous);
    }
}