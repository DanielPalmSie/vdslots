<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Requests;

use IT\Abstractions\AbstractRequest;
use IT\Pacg\Client\PacgClient;
use IT\Pacg\Requests\PacgRequest;
use IT\Abstractions\AbstractResponse;
use IT\Abstractions\AbstractEntity;
use Tests\Unit\Phive\Modules\Licensed\IT\Support;

/**
 * Class AbstractRequestTest
 */
class AbstractRequestTest extends Support
{
    /**
     * @var AbstractRequest
     */
    protected $stub;

    /**
     * @var string
     */
    protected $stub_type = PacgRequest::class;

    /**
     * AbstractRequestTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     * @throws \ReflectionException
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = $this->getStub(self::getClientMock());
    }

    public function testClientType()
    {
        $this->assertInstanceOf(AbstractRequest::class, $this->stub);
    }

    public function testGetNetworkId()
    {
        $value = 55;
        $this->stub->setNetworkId($value);
        $value_return = $this->stub->getNetworkId();
        $this->assertTrue(is_string($value_return));
        $this->assertEquals($value, $value_return);
    }

    public function testSetIdCn()
    {
        $value = 55;
        $this->stub->setIdCn($value);
        $id_cn= self::setPropertyAccessiblePublic(PacgRequest::class, 'id_cn');
        $this->assertEquals($value, $id_cn->getValue($this->stub));
    }

    public function testGetIdFsc()
    {
        $value = 55;
        $this->stub->setIdFsc($value);
        $value_return = $this->stub->getIdFsc();
        $this->assertTrue(is_string($value_return));
        $this->assertEquals($value, $value_return);
    }

    public function testSetIdFsc()
    {
        $value = 55;
        $this->stub->setIdFsc($value);
        $id_fsc = self::setPropertyAccessiblePublic(PacgRequest::class, 'id_fsc');
        $this->assertEquals($value, $id_fsc->getValue($this->stub));
    }

    public function testSetCommonAttributes()
    {
        $test_array = ['test' => 'value test'];

        $this->stub = $this->getStub(self::getClientMock(), '1', '2', '3', ['setAccountFields']);

        $this->stub->method("setAccountFields")
            ->will($this->returnArgument(0));

        $set_common_attributes = self::getAccessibleMethod(
            $this->stub_type,
            'setCommonAttributes'
        );
        $set_common_attributes_return  = $set_common_attributes->invokeArgs($this->stub, [$test_array]);

        $this->assertTrue(array_key_exists('requestElements', $set_common_attributes_return));
        $this->assertTrue(array_key_exists('idFsc', $set_common_attributes_return['requestElements']));
        $this->assertTrue(array_key_exists('idCn', $set_common_attributes_return['requestElements']));
        $this->assertTrue(array_key_exists('idRete', $set_common_attributes_return['requestElements']));

        $key_test = key($test_array);
        $this->assertTrue(array_key_exists($key_test, $set_common_attributes_return['requestElements']));

        $value_test = $test_array[$key_test];
        $this->assertEquals($value_test, $set_common_attributes_return['requestElements'][$key_test]);
    }

    public function testGetResult()
    {
        $std_lass_mock = \Mockery::mock(\stdClass::class);
        self::setValueInPrivateProperty(
            $this->stub,
            $this->stub_type,
            'request_return',
            $std_lass_mock
        );

        $this->assertInstanceOf(\stdClass::class, $this->stub->getResult());
    }

    public function testRequest()
    {
        $methods = ['setCommonAttributes', 'execute'];
        $this->stub = $this->getStub(self::getClientMock(), '1', '2', '3', $methods);

        $response = $this->stub->request($this->getEntity());

        $this->assertInstanceOf(AbstractResponse::class, $response);
    }

    /**
     * @param PacgClient $client
     * @param string $id_fsc
     * @param string $id_cn
     * @param string $network_id
     * @param array $methods
     * @return AbstractRequest
     * @throws \ReflectionException
     */
    protected function getStub(
        PacgClient $client,
        string $id_fsc = '1',
        string $id_cn = '2',
        string $network_id = '3',
        array $methods = []
    ): AbstractRequest
    {
        $stub = $this->getMockBuilder($this->stub_type)
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        self::setValueInPrivateProperty(
            $stub,
            PacgRequest::class,
            'client',
            $client
        );

        self::setValueInPrivateProperty(
            $stub,
            PacgRequest::class,
            'id_fsc',
            $id_fsc
        );

        self::setValueInPrivateProperty(
            $stub,
            PacgRequest::class,
            'id_cn',
            $id_cn
        );

        self::setValueInPrivateProperty(
            $stub,
            PacgRequest::class,
            'network_id',
            $network_id
        );

        self::setValueInPrivateProperty(
            $stub,
            PacgRequest::class,
            'account_cn_id',
            $id_cn
        );

        self::setValueInPrivateProperty(
            $stub,
            PacgRequest::class,
            'account_network_id',
            $network_id
        );

        return $stub;
    }

    /**
     * @return AbstractResponse
     */
    protected function getResponse(): AbstractResponse
    {
        return $this->getMockBuilder(AbstractResponse::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @return PacgClient
     */
    protected static function getClientMock(): PacgClient
    {
        $soap_client_mock = \Mockery::mock(PacgClient::class)->makePartial();
        $soap_client_mock->shouldReceive([
            'exec' => new \stdClass()
        ]);

        return $soap_client_mock;
    }

    /**
     * @param array $methods
     * @return AbstractEntity
     */
    protected function getEntity(array $methods = []): AbstractEntity
    {
        return $this->getMockBuilder(AbstractEntity::class)
            ->onlyMethods($methods)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
}