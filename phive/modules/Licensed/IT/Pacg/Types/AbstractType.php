<?php
namespace IT\Pacg\Types;

use IT\Pacg\Types\Traits\ValidationRuleTrait;

/**
 * Class AbstractType
 * @package IT\Pacg\Types
 */
abstract class AbstractType
{
    use ValidationRuleTrait;

    /**
     * @var array
     */
    protected static $codes = [
        0 => "Generic Validation error",
        1 => "The value must be a string",
        2 => "The min length of the value must be %1 characters",
        3 => "The max length of the value must be %1 characters",
        4 => "The exact length of the value must be %1 characters",
        5 => "The value must be a integer",
        6 => "The document type is not valid",
        7 => "The gender type is not valid",
        8 => "The email format is not valid",
        9 => "Gaming type code not valid",
        10 => "Gaming family code not valid",
        11 => "Transaction Reason code not valid",
        12 => "Limit type code not valid"
    ];

    /**
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * @return string
     */
    public function toString(): string
    {
        return '';
    }

    /**
     * @param int $code
     * @param array $params
     * @return string
     */
    protected function getErrorDescription(int $code, array $params = []): string
    {
        if (isset(self::$codes[$code])) {
            return strtr(self::$codes[$code], $params);
        } else {
            return (self::$codes[0]);
        }
    }
}