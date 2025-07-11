<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use http\Exception;
use IT\Pacg\Services\UpdateAccountLimitEntity;
use IT\Pacg\Types\LimitType;

/**
 * Class UpdateAccountLimitEntityTest
 */
class UpdateAccountLimitEntityTest extends AbstractServiceTest
{
    /**
     * @var UpdateAccountLimitEntity
     */
    protected $stub;

    /**
     * UpdateAccountLimitEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(UpdateAccountLimitEntity::class)->makePartial();
    }

    public function testSuccessSettingUpdateAccountLimitEntity()
    {
        $payload = $this->getUpdateAccountLimitPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('gestioneLimite', $return_to_array));
        $this->assertTrue(array_key_exists('limite', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['gestioneLimite'], $return_to_array['limit_management']);

        $this->assertInstanceOf(LimitType::class, $this->stub->limit);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getUpdateAccountLimitPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('limit_management', $this->stub->errors));
        $this->assertTrue(array_key_exists('limit', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('limit_management', $this->stub->errors));
        $this->assertTrue(! array_key_exists('limit', $this->stub->errors));

        unset($payload['limit_management']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('limit_management', $this->stub->errors));
        $this->assertTrue(! array_key_exists('limit', $this->stub->errors));

        unset($payload['limit']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('limit_management', $this->stub->errors));
        $this->assertTrue(array_key_exists('limit', $this->stub->errors));
    }

    /**
     * @throws \Exception
     */
    public function testErrorToSetLimitException()
    {
        $payload = $this->getUpdateAccountLimitPayload();
        unset($payload['limit']['limit_type']);
        $this->expectException(\Exception::class);
        $this->stub->setLimit($payload['limit']);
    }

    /**
     * @return array
     */
    private function getUpdateAccountLimitPayload(): array
    {
        return [
            'account_code' => 4002,
            'limit_management' => 1,
            'limit' => [
                'limit_type' => 1,
                'amount' => 100,

            ],
            'transaction_id' => time()
        ];
    }
}