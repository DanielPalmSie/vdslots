<?php

namespace IdScan\Exceptions;

use Exception;

class MissingSettingException extends Exception
{

    /**
     * @param null $setting
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($setting = null, $code = 0, Exception $previous = null)
    {
        parent::__construct('Missing setting ' . $setting, $code, $previous);
    }
}