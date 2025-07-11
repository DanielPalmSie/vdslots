<?php
namespace Tests\Unit\Phive\Modules\Licensed\IT\Pacg\Services;

use IT\Pacg\Services\AccountMigrationEntity;
use IT\Pacg\Types\BonusDetailListType;
use IT\Pacg\Types\DateTimeType;

/**
 * Class AccountMigrationEntityTest
 */
class AccountMigrationEntityTest extends AbstractServiceTest
{
    /**
     * @var AccountMigrationEntity
     */
    protected $stub;

    /**
     * AccountMigrationEntityTest constructor.
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->stub = \Mockery::mock(AccountMigrationEntity::class)->makePartial();
    }

    public function testSuccessSettingAccountMigrationEntity()
    {
        $payload = $this->getAccountMigrationPayload();
        $this->stub->fill($payload);
        $return_to_array = $this->stub->toArray();
        $this->assertTrue(is_array($return_to_array));

        $this->assertTrue(array_key_exists('codiceContoOriginario', $return_to_array));
        $this->assertTrue(array_key_exists('idReteContoDestinazione', $return_to_array));
        $this->assertTrue(array_key_exists('idCnContoDestinazione', $return_to_array));
        $this->assertTrue(array_key_exists('codiceContoDestinazione', $return_to_array));
        $this->assertTrue(array_key_exists('codiceFiscale', $return_to_array));
        $this->assertTrue(array_key_exists('importoSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('importoBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('numDettagliBonusSaldo', $return_to_array));
        $this->assertTrue(array_key_exists('dettaglioBonusSaldo', $return_to_array));

        $this->assertEquals($payload['account_code'], $return_to_array['codiceContoOriginario']);
        $this->assertEquals($payload['account_sales_network_id_destination'], $return_to_array['idReteContoDestinazione']);
        $this->assertEquals($payload['account_network_id_destination'], $return_to_array['idCnContoDestinazione']);
        $this->assertEquals($payload['account_code_destination'], $return_to_array['codiceContoDestinazione']);
        $this->assertEquals($payload['tax_code'], $return_to_array['codiceFiscale']);
        $this->assertEquals($payload['balance_amount'], $return_to_array['importoSaldo']);
        $this->assertEquals($payload['balance_bonus_amount'], $return_to_array['importoBonusSaldo']);

        $this->assertInstanceOf(BonusDetailListType::class, $this->stub->balance_bonus_detail);
    }

    public function testSetInvalidPayload()
    {
        $payload = $this->getAccountMigrationPayload();
        $this->stub->validate([]);
        $this->assertTrue(is_array($this->stub->errors));
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_bonus_detail', $this->stub->errors));

        unset($payload['account_code']);
        $this->stub->validate($payload);
        $this->assertCount(1, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_detail', $this->stub->errors));

        unset($payload['account_sales_network_id_destination']);
        $this->stub->validate($payload);
        $this->assertCount(2, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_detail', $this->stub->errors));

        unset($payload['account_network_id_destination']);
        $this->stub->validate($payload);
        $this->assertCount(3, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_detail', $this->stub->errors));

        unset($payload['account_code_destination']);
        $this->stub->validate($payload);
        $this->assertCount(4, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(! array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_detail', $this->stub->errors));

        unset($payload['tax_code']);
        $this->stub->validate($payload);
        $this->assertCount(5, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(! array_key_exists('balance_bonus_detail', $this->stub->errors));


        unset($payload['balance_bonus_detail']);
        $this->stub->validate($payload);
        $this->assertCount(6, $this->stub->errors);
        $this->assertTrue(array_key_exists('account_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_sales_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_network_id_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('account_code_destination', $this->stub->errors));
        $this->assertTrue(array_key_exists('tax_code', $this->stub->errors));
        $this->assertTrue(array_key_exists('balance_bonus_detail', $this->stub->errors));
    }

    public function testErrorToSetBalanceBonusDetailException()
    {
        $payload = $this->getAccountMigrationPayload();
        unset($payload['balance_bonus_detail'][0]['gaming_family']);
        $this->expectException(\Exception::class);
        $this->stub->setBonusDetail($payload['balance_bonus_detail']);
    }


    /**
     * @return array
     */
    private function getAccountMigrationPayload(): array
    {
        return [
            'account_code' => 4002,
            'account_sales_network_id_destination' => '321',
            'account_network_id_destination' => '14',
            'account_code_destination' => '3212',
            'tax_code' => 'TSTDSA85P57F205D',
            'balance_bonus_detail' => [
                [
                    'gaming_family' => '1',
                    'gaming_type' => '1',
                    'bonus_amount' => '1'
                ]
            ],
            'transaction_id' => time(),
        ];
    }
}