<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Loaders;

use IT\Abstractions\AbstractAction;
use IT\Loaders\LoaderAction;
use IT\Pacg\Actions\Enums\ActionsEnum;
use IT\Pacg\Client\PacgClient;
use IT\Pgda\Client\PgdaClient;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

/**
 * Class LoaderActionTest
 */
class LoaderActionTest extends Support
{
    /**
     * @var LoaderAction
     */
    protected $stub;

    /**
     * LoaderActionTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws \ReflectionException
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(LoaderAction::class)->makePartial();
        $this->mockClient();
        $this->mockClient(PgdaClient::class, 'client_pgda');
    }

    public function testGetClient()
    {
        $client = $this->stub->getPacgClient($this->getConfig());
        $this->assertInstanceOf(PacgClient::class, $client);
    }

    public function testGetAction()
    {
        $enum_actions_reflection = new \ReflectionClass(ActionsEnum::class);
        foreach ($enum_actions_reflection->getConstants() as $constant => $action_name) {
            $action = $this->stub->getAction($action_name);
            $this->assertInstanceOf(AbstractAction::class, $action);
        }
    }

    public function testGetActionException()
    {
        $this->expectException(\Exception::class);
        $this->stub->getAction('test', []);
    }

    /**
     * @param string $class
     * @param string $property
     * @throws \ReflectionException
     */
    private function mockClient(string $class = PacgClient::class, string $property = 'client_pacg')
    {
        $client = \Mockery::mock($class);
        $this->setValueInPrivateProperty($this->stub, LoaderAction::class, $property, $client);
    }

    /**
     * @return array
     */
    protected function getConfig(): array
    {
        return (new \Licensed())->getSetting('IT');
    }
}