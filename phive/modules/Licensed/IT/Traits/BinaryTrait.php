<?php
namespace IT\Traits;

/**
 * Trait BinaryTrait
 * @package IT\Pgda\Services
 */
trait BinaryTrait
{
    /**
     * @param array $data
     * @param string $format
     * @return false|string
     */
    public static function convert(array $data, string $format)
    {
        return pack($format, ...array_values($data));
    }

    /**
     * @param string $packed
     * @param string $format
     * @return array|false
     */
    public static function deconvert(string $packed, string $format)
    {
        return unpack($format, $packed);
    }
}
