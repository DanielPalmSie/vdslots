<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Types;

use IT\Pacg\Types\LimitType;

/**
 * Class LimitTypeTest
 */
class LimitTypeTest extends AbstractTypeTest
{
    /**
     * @var LimitType
     */
    protected $stub;

    /**
     * LimitTypeTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(LimitType::class)->makePartial();
    }

    public function testSuccessSettingLimitType()
    {
        $payload = $this->getLimitPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('tipoLimite', $return_to_array));
        $this->assertTrue(array_key_exists('importo', $return_to_array));

        $this->assertEquals($payload['limit_type'], $return_to_array['tipoLimite']);
        $this->assertEquals($payload['amount'], $return_to_array['importo']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getLimitPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('limit_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('amount', $this->stub->errors));

        unset($payload['limit_type']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('limit_type', $this->stub->errors));
        $this->assertTrue(!array_key_exists('amount', $this->stub->errors));

        unset($payload['amount']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('limit_type', $this->stub->errors));
        $this->assertTrue(array_key_exists('amount', $this->stub->errors));
    }

    public function testLimitPayloadValidateFail()
    {
        $payload = $this->getLimitPayload();
        $payload['limit_type'] = '4';
        $this->stub->validate($payload);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('limit_type', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getLimitPayload(): array
    {
        return [
            'limit_type' => 1,
            'amount' => 2000
        ];
    }

}