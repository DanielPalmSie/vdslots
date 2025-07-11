<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\UpdateAccountStatusEntity;

/**
 * Class UpdateAccountStatusEntityTest
 */
class UpdateAccountStatusEntityTest extends AbstractServiceTest
{
    /**
     * @var UpdateAccountStatusEntity
     */
    protected $stub;

    /**
     * UpdateAccountStatusEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(UpdateAccountStatusEntity::class)->makePartial();
    }

    public function testSuccessSettingUpdateAccountStatusEntity()
    {
        $payload = $this->getUpdateAccountStatusPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('stato', $return_to_array));
        $this->assertTrue(array_key_exists('causale', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['status'], $return_to_array['stato']);
        $this->assertEquals($payload['reason'], $return_to_array['causale']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getUpdateAccountStatusPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('status', $this->stub->errors));
        $this->assertTrue(array_key_exists('reason', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('status', $this->stub->errors));
        $this->assertTrue(! array_key_exists('reason', $this->stub->errors));

        unset($payload['status']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('status', $this->stub->errors));
        $this->assertTrue(! array_key_exists('reason', $this->stub->errors));

        unset($payload['reason']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('status', $this->stub->errors));
        $this->assertTrue(array_key_exists('reason', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getUpdateAccountStatusPayload(): array
    {
        return [
            'account_code' => 4002,
            'status' => '1',
            'reason' => 'test',
            'transaction_id' => time(),
        ];
    }
}