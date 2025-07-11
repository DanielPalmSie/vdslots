<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT;

require_once __DIR__ . '/../../../../../../../phive/modules/Licensed/IT/IT.php';

use PHPUnit\Framework\TestCase;

/**
 * Class Test
 */
class Support extends TestCase
{
    /**
     * @param $class_name
     * @param $method_name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public static function getAccessibleMethod($class_name, $method_name): \ReflectionMethod
    {
        $class = new \ReflectionClass($class_name);
        $method = $class->getMethod($method_name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @param $class_name
     * @param $property_name
     * @return \ReflectionProperty
     * @throws \ReflectionException
     */
    public static function setPropertyAccessiblePublic($class_name, $property_name): \ReflectionProperty
    {
        $reflector = new \ReflectionProperty($class_name, $property_name);
        $reflector->setAccessible(true);
        return $reflector;
    }
    /**
     * @param $object
     * @param $class_name
     * @param $property_name
     * @param $value
     * @throws \ReflectionException
     */
    public static function setValueInPrivateProperty($object, $class_name, $property_name, $value)
    {
        $reflector = self::setPropertyAccessiblePublic($class_name, $property_name);
        $reflector->setValue($object, $value);
    }

    /**
     * @return string
     */
    protected function flushAll()
    {
        return exec('redis-cli flushall');
    }
}
