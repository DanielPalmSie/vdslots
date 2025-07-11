<?php

abstract class AbstractCode
{
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