<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Services;

use IT\Services\ErrorFormatterService;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

/**
 * Class ErrorFormatterServiceTest
 */
class ErrorFormatterServiceTest extends Support
{
    public function testMountErrorMessage()
    {
        $error_data = ['test', ['test_key' => 'message']];
        $mock =  \Mockery::mock(ErrorFormatterService::class);
        $mount_error_message = self::getAccessibleMethod(ErrorFormatterService::class, 'mountErrorMessage');
        $return = $mount_error_message->invokeArgs($mock, $error_data);
        $expected_result = "test : test_key - message\n";
        $this->assertEquals($expected_result, $return);
    }

    public function testFormat()
    {
        $mock = \Mockery::mock(ErrorFormatterService::class)->makePartial();
        $error_data = ['test' => ['test_key' => 'message']];
        $return = $mock->format($error_data);
        $expected_result = "test : test_key - message\n";

        $this->assertEquals($expected_result, $return);
    }
}
