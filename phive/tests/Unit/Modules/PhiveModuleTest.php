<?php

namespace Tests\Unit\Modules;

use PHPUnit\Framework\TestCase;

abstract class PhiveModuleTest extends TestCase
{
    protected function mockPhiveModule($originalObject, array $methods)
    {
        $mock = $this->getMockBuilder(get_class($originalObject))
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();

        // Inject the existing logger configuration into the mock
        $reflection = new \ReflectionClass($originalObject);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $property->setValue($mock, $property->getValue($originalObject));
        }

        return $mock;
    }
}
