<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT;

use IT\Loaders\LoaderAction;
use IT\Abstractions\AbstractAction;
use IT\Abstractions\AbstractResponse;

/**
 * Class ITTest
 */
class ITTest extends Support
{
    /**
     * @var \IT
     */
    protected $stub;

    /**
     * ITTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = $this->getStub();
    }

    public function testGetLoaderAction()
    {
        $config_return = $this->getConfig();
        $this->stub = $this->getStub(['getAllLicSettings']);
        $this->stub->method('getAllLicSettings')->will($this->returnValue($config_return));
        $get_loader_action = self::getAccessibleMethod(\IT::class, 'getLoaderAction');
        $return = $get_loader_action->invoke($this->stub);

        $this->assertInstanceOf(LoaderAction::class, $return);
    }

    public function testPacgResponse()
    {
        $response = \Mockery::mock(AbstractResponse::class);
        $array_response = [];
        $response->shouldReceive(['toArray' => $array_response]);
        $pacg_response = self::getAccessibleMethod(\IT::class, 'response');
        $response_result = $pacg_response->invokeArgs($this->stub, [$response]);

        $this->assertEquals($array_response, $response_result);
    }

    public function testPacgExceptionResponse()
    {
        $exception = \Mockery::mock(\Exception::class);
        $pacg_exception_response = self::getAccessibleMethod(\IT::class, 'exceptionResponse');
        $exception_result = $pacg_exception_response->invokeArgs($this->stub, [$exception]);

        $this->assertTrue(is_array($exception_result));
        $this->assertTrue(array_key_exists('code', $exception_result));
        $this->assertTrue(array_key_exists('message', $exception_result));
        $this->assertTrue(array_key_exists('trace', $exception_result));
        $this->assertEquals(500, $exception_result['code']);
    }

    public function testExecAction()
    {
        $response_mock = \Mockery::mock(AbstractResponse::class);
        $array_response = ['test'];
        $response_mock->shouldReceive(['toArray' => $array_response]);

        $action_mock = \Mockery::mock(AbstractAction::class);
        $action_mock->shouldReceive('execute')
            ->andReturn($response_mock);

        $loader_action_mock = \Mockery::mock(LoaderAction::class);
        $loader_action_mock
            ->shouldReceive('getAction')
            ->andReturn($action_mock);

        self::setValueInPrivateProperty($this->stub, \IT::class, 'loader_action', $loader_action_mock);

        $exec_action = self::getAccessibleMethod(\IT::class, 'execAction');
        $exec_action_return  = $exec_action->invokeArgs($this->stub, ['', []]);

        $this->assertEquals($array_response, $exec_action_return);
    }

    /**
     * @param array $methods
     * @return \IT
     */
    protected function getStub(array $methods = []): \IT
    {
        return $this->getMockBuilder(\IT::class)
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @return array
     */
    protected function getConfig(): array
    {
        return (new \Licensed())->getSetting('IT');
    }
}