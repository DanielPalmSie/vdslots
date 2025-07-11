<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\SubregistrationEntity;

/**
 * Class SubregistrationEntityTest
 */
class SubregistrationEntityTest extends AbstractServiceTest
{
    /**
     * @var SubregistrationEntity
     */
    protected $stub;

    /**
     * SubregistrationEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(SubregistrationEntity::class)->makePartial();
    }

    public function testSuccessSettingSubregistrationEntity()
    {
        $payload = $this->getSubregistrationPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('importoSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('importoBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoSaldo']);
        $this->assertEquals($payload['balance_bonus_amount'], $return_to_array['importoBonusSaldo']);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getSubregistrationPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_bonus_amount', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_amount', $this->stub->errors));

        unset($payload['balance_amount']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_amount', $this->stub->errors));

        unset($payload['balance_bonus_amount']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_bonus_amount', $this->stub->errors));
    }

    /**
     * @return array
     */
    private function getSubregistrationPayload(): array
    {
        return [
            'account_code' => 4002,
            'balance_amount' => '1',
            'balance_bonus_amount' => '1',
            'transaction_id' => time()
        ];
    }
}