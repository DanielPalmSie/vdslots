<?php

class ExampleTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testIsValueIsTrue()
    {
        $value = true;
        $this->assertTrue($value);
    }

    public function testIsValueEqualsFive()
    {
        $value = 5;
        $this->assertEquals(5, $value);
    }
}