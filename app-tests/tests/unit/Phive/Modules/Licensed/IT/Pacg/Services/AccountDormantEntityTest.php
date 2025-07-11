<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\AccountDormantEntity;
use IT\Pacg\Types\DateType;

/**
 * Class AccountDormantEntityTest
 */
class AccountDormantEntityTest extends AbstractServiceTest
{
    /**
     * @var AccountDormantEntity
     */
    protected $stub;

    /**
     * AccountDormantEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(AccountDormantEntity::class)->makePartial();
    }

    public function testSuccessSettingAccountDormantEntity()
    {
        $payload = $this->getAccountDormantPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceConto', $return_to_array));
        $this->assertTrue(array_key_exists('dataDormiente', $return_to_array));
        $this->assertTrue(array_key_exists('importoSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('idTransazione', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceConto']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoSaldo']);

        $this->assertInstanceOf(DateType::class, $this->stub->date_dormant);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getAccountDormantPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_dormant', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('date_dormant', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['date_dormant']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_dormant', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_amount', $this->stub->errors));

        unset($payload['balance_amount']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('date_dormant', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_amount', $this->stub->errors));
    }

    public function testErrorToSetDateDormantException()
    {
        $payload = $this->getAccountDormantPayload();
        unset($payload['date_dormant']['day']);
        $this->expectException(\Exception::class);
        $this->stub->setDateDormant($payload['date_dormant']);
    }

    /**
     * @return array
     */
    private function getAccountDormantPayload(): array
    {
        return [
            'account_code' => 4002,
            'date_dormant' => [
                'day' => '01',
                'month' => '04',
                'year' => '2020'
            ],
            'balance_amount' => 100,
            'transaction_id' => time()
        ];
    }
}