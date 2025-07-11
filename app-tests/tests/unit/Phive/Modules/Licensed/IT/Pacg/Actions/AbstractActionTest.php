<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Actions;

use IT\Abstractions\AbstractAction;
use IT\Pacg\Client\PacgClient;
use IT\Abstractions\AbstractRequest;
use IT\Abstractions\AbstractResponse;
use IT\Abstractions\AbstractEntity;
use IT\Services\ErrorFormatterService;
use Mockery\Mock;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

/**
 * Class AbstractActionTest
 */
class AbstractActionTest extends Support
{
    /**
     * @var AbstractAction
     */
    protected $stub;

    /**
     * @var string
     */
    protected $stub_type = AbstractAction::class;

    /**
     * @var string
     */
    protected $request_name = AbstractRequest::class;

    /**
     * @var string
     */
    protected $entity_name = AbstractEntity::class;

    /**
     * @var string
     */
    protected $response_name = AbstractResponse::class;

    /**
     * AbstractActionTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = $this->getStub();
    }

    public function testGetNewErrorFormatter()
    {
        $get_new_error_formatter = self::getAccessibleMethod(
            $this->stub_type,
            'getNewErrorFormatter'
        );

        $return = $get_new_error_formatter->invoke($this->getStub());

        $this->assertInstanceOf(ErrorFormatterService::class, $return);
    }

    public function testGetRequest()
    {
        if ($this->stub_type == AbstractAction::class) {
            return;
        }
        
        self::setValueInPrivateProperty(
            $this->stub,
            $this->stub_type,
            'client',
            self::getMockClient(PacgClient::class)
        );
        $settings = [
                'wss' => [
                    'username' => 'username'
                ],
                'id_fsc' => '1',
                'id_cn' => '2',
                'network_id' => '3',
            'pacg' => [
                'account_network_id' => '3',
                'account_cn_id' => '3',
            ],
        ];

        self::setValueInPrivateProperty(
            $this->stub,
            $this->stub_type,
            'settings',
            $settings
        );

        $request = $this->stub->getRequest();

        $this->assertInstanceOf($this->request_name, $request);
    }

    public function testGetEntity()
    {
        if ($this->stub_type == AbstractAction::class) {
            return;
        }

        $entity = $this->stub->getEntity();

        $this->assertInstanceOf(AbstractEntity::class, $entity);
    }

    public function testGetNewException()
    {
        $get_new_exception = self::getAccessibleMethod($this->stub_type, 'getNewException');
        $exception = $get_new_exception->invokeArgs($this->stub, [[]]);

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testLoadPayloadData()
    {
        if ($this->stub_type == AbstractAction::class) {
            $this->stub = $this->getStub(['getEntity']);
            $entity = $this->getMockEntity($this->entity_name);
            $entity->shouldReceive(['fill', 'validate']);
            $this->stub->method('getEntity')->will($this->returnValue($entity));
        }

        $load_payload_data = self::getAccessibleMethod($this->stub_type, 'loadPayloadData');
        $entity = $load_payload_data->invokeArgs($this->stub, [[]]);

        $this->assertInstanceOf($this->entity_name, $entity);
    }

    public function testExecute()
    {
        $this->stub = $this->getStub(['getRequest', 'loadPayloadData']);
        $entity = $this->getMockEntity($this->entity_name);
        $request = $this->getMockRequest($this->request_name);
        $response = $this->getMockResponse($this->response_name);
        $request->shouldReceive(['request' => $response]);

        $this->stub->method('getRequest')->will($this->returnValue($request));
        $this->stub->method('loadPayloadData')->will($this->returnValue($entity));

        $response_return = $this->stub->execute([]);

        $this->assertInstanceOf(AbstractResponse::class, $response_return);
    }

    /**
     * @param array $should_receive
     * @return \ReflectionClass
     */
    private static function getMockReflectionClass(array $should_receive): \ReflectionClass
    {
        $mock = \Mockery::mock(\ReflectionClass::class);
        $mock->shouldReceive($should_receive);
        return $mock;
    }

    /**
     * @param string $client
     * @return PacgClient
     */
    private static function getMockClient(string $client): PacgClient
    {
        return self::getMock($client);
    }

    /**
     * @param string $request
     * @return AbstractRequest
     */
    private static function getMockRequest(string $request): AbstractRequest
    {
        return self::getMock($request);
    }

    /**
     * @param string $entity
     * @return AbstractEntity
     */
    private static function getMockEntity(string $entity): AbstractEntity
    {
        return self::getMock($entity);
    }

    /**
     * @param string $response
     * @return AbstractResponse
     */
    private static function getMockResponse(string $response): AbstractResponse
    {
        return self::getMock($response);
    }

    /**
     * @param $name
     * @return Mock
     */
    private static function getMock($name)
    {
        return \Mockery::mock($name)->makePartial();
    }

    /**
     * @param array $methods
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStub(array $methods = [])
    {
        return $this->getMockBuilder($this->stub_type)
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
}