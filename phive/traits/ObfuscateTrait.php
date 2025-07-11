<?php

use Illuminate\Support\Arr;

/**
 * A trait for obfuscating array values, which can be used to mask sensitive log data for example.
 */
trait ObfuscateTrait
{
    /**
     * Obfuscates the scalar values in an array.
     *
     * @param array|null $array The array to obfuscate.
     * @param array|null $keys <p>An array of dot notation keys whose scalar values will be obfuscated.
     * If <b>$keys</b> is empty or null then all values are recursively obfuscated.
     * </p>
     * @return array|null The obfuscated array.
     * @example obfuscateArray(['foo' => ['bar' => 'abcdef', 'baz' => 'pqrstu']], ['foo.baz'])    // ['foo' => ['bar' => 'abcdef', 'baz' => 'pq**tu']]
     */
    protected function obfuscateArray(array &$array = null, array $keys = null)
    {
        if (!$array) {
            return $array;
        }

        if (empty($keys)) {
            foreach ($array as &$v) {
                if (is_array($v)) {
                    $this->obfuscateArray($v);
                } elseif (is_scalar($v)) {
                    $v = $this->obfuscateValue($v);
                }
            }
        } else {
            foreach ($keys as $k) {
                if (Arr::has($array, $k)) {
                    Arr::set($array, $k, $this->obfuscateValue(Arr::get($array, $k)));
                }
            }
        }

        return $array;
    }

    /**
     * Obfuscates a scalar value by replacing all characters with '*' except for the first and last 2.
     * Note that an integer will be returned as a string, since we replace some digits with '*'.
     *
     * @param mixed $s The value to obfuscate.
     * @return mixed The obfuscated value.
     * @example obfuscate('abcd1234')     // 'ab****34'
     *          obfuscate(12345678)       // '12****78'
     */
    protected function obfuscateValue($s = null)
    {
        if (!is_scalar($s)) {
            return $s;
        }

        $s = (string)$s;
        if (($len = strlen($s)) < 6) {
            return substr($s, 0, 2) . str_repeat('*', max(0, $len - 2));
        }
        return substr($s, 0, 2) . str_repeat('*', $len - 4) . substr($s,-2);
    }
}