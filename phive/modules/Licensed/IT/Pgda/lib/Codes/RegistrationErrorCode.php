<?php

/**
 * Section 8.1
 * Class ReturnCode
 */
class RegistrationErrorCode
{
    /**
     * TODO Add the other codes
     * @var array
     */
    protected static $codes = [
        1000 => "Generic message reading error",
        1001 => "Currently being processed - please wait"
    ];


    /**
     * @param int $code
     * @param array $params
     * @return string
     */
    protected function getCodeDescription(int $code, array $params = []): string
    {
        if (isset(self::$codes[$code])) {
            return strtr(self::$codes[$code], $params);
        } else {
            return (self::$codes[0]);
        }
    }

}